<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Models\Book;
use App\Models\Genre;
use App\Services\GoogleBooksService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * 書籍の一覧表示・検索・登録・更新・削除を管理するコントローラー。
 *
 * 書籍一覧ではキーワード検索、ジャンル絞り込み、ソートに対応する。
 * 登録・更新・削除では、書籍の作成者に対する認可を行う。
 */
class BookController extends Controller
{
    /**
     * 書籍一覧を表示する。
     *
     * タイトル・著者によるキーワード検索、ジャンル絞り込み、
     * 新しい順・古い順・評価順・タイトル順の並び替えに対応する。
     */
    public function index(Request $request)
    {
        $query = Book::with('genres')
            ->withAvg('reviews', 'rating');

        if ($request->filled('keyword')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%'.$request->keyword.'%')
                    ->orWhere('author', 'like', '%'.$request->keyword.'%');
            });
        }

        if ($request->filled('genre')) {
            $genreId = $request->input('genre');

            $query->whereHas('genres', function ($query) use ($genreId) {
                $query->where('genres.id', $genreId);
            });
        }

        switch ($request->input('sort', 'newest')) {
            case 'oldest':
                $query->oldest();
                break;

            case 'rating':
                $query->orderByDesc('reviews_avg_rating');
                break;

            case 'title':
                $query->orderBy('title');
                break;

            case 'newest':
            default:
                $query->latest();
                break;
        }

        $books = $query
            ->paginate(10)
            ->withQueryString();

        $genres = Genre::all();

        return view('books.index', compact('books', 'genres'));
    }

    /**
     * 書籍の新規登録画面を表示する。
     *
     * 登録時に選択できるジャンル一覧を取得して画面に渡す。
     */
    public function create()
    {
        $genres = Genre::all();

        return view('books.create', compact('genres'));
    }

    /**
     * 新しい書籍を登録する。
     *
     * 書籍本体を保存した後、中間テーブルを利用して
     * 選択されたジャンルを紐付ける。
     *
     * @param  StoreBookRequest  $request  検証済みの書籍登録データ
     */
    public function store(StoreBookRequest $request)
    {
        $validated = $request->validated();

        $genreIds = $validated['genres'];
        unset($validated['genres']);

        $validated['created_by'] = auth()->id();

        $book = DB::transaction(function () use ($validated, $genreIds) {
            $book = Book::create($validated);

            $book->genres()->sync($genreIds);

            return $book;
        });

        return redirect()
            ->route('books.show', $book)
            ->with('success', '書籍を登録しました。');
    }

    /**
     * 指定された書籍の詳細を表示する。
     *
     * ジャンル・レビュー・お気に入り数など、
     * 詳細画面に必要な関連情報をあわせて取得する。
     *
     * @param  Book  $book  表示対象の書籍
     */
    public function show(Book $book)
    {
        $book->load([
            'genres',
            'reviews' => function ($query) {
                $query->with('user')
                    ->withCount('likes')
                    ->latest();
            },
        ])->loadCount([
            'favorites',
            'reviews',
        ]);

        return view('books.show', compact('book'));
    }

    /**
     * 書籍の編集画面を表示する。
     *
     * Policyにより、書籍の作成者だけが編集できる。
     *
     * @param  Book  $book  編集対象の書籍
     */
    public function edit(Book $book)
    {
        $this->authorize('update', $book);

        $genres = Genre::all();

        $book->load('genres');

        return view('books.edit', compact('book', 'genres'));
    }

    /**
     * 書籍情報を更新する。
     *
     * 書籍本体の情報を更新し、選択されたジャンルとの紐付けも同期する。
     * Policyにより、書籍の作成者だけが更新できる。
     *
     * @param  UpdateBookRequest  $request  検証済みの書籍更新データ
     * @param  Book  $book  更新対象の書籍
     */
    public function update(UpdateBookRequest $request, Book $book)
    {
        $this->authorize('update', $book);

        $validated = $request->validated();

        $genreIds = $validated['genres'];
        unset($validated['genres']);

        DB::transaction(function () use ($book, $validated, $genreIds) {
            $book->update($validated);

            // 現在のジャンルとの紐付けを、送信されたジャンルへ置き換える
            $book->genres()->sync($genreIds);
        });

        return redirect()
            ->route('books.show', $book)
            ->with('success', '書籍情報を更新しました。');
    }

    /**
     * 指定された書籍を削除する。
     *
     * Policyにより、書籍の作成者だけが削除できる。
     *
     * @param  Book  $book  削除対象の書籍
     */
    public function destroy(Book $book)
    {
        $this->authorize('delete', $book);

        $book->delete();

        return redirect()
            ->route('books.index')
            ->with('success', '書籍を削除しました。');
    }

    /**
     * ISBNをもとにGoogle Books APIから書籍情報を取得する。
     *
     * ISBNが13桁でない場合や、該当書籍を取得できない場合は
     * エラーレスポンスを返す。
     *
     * @param  Request  $request  ISBNを含む検索リクエスト
     */
    public function searchByIsbn(
        string $isbn,
        GoogleBooksService $googleBooksService
    ): JsonResponse {
        if (! preg_match('/^\d{13}$/', $isbn)) {
            return response()->json([
                'error' => 'ISBNは13桁で入力してください。',
            ], 422);
        }

        // 外部APIとの通信処理はサービスクラスへ委譲する
        $bookData = $googleBooksService->searchByIsbn($isbn);

        if ($bookData === null) {
            return response()->json([
                'error' => '該当する書籍が見つかりませんでした。',
            ], 404);
        }

        return response()->json($bookData);
    }
}
