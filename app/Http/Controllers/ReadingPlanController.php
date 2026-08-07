<?php

namespace App\Http\Controllers;

use App\Enums\ReadingPlanStatus;
use App\Http\Requests\StoreReadingPlanRequest;
use App\Http\Requests\UpdateReadingPlanRequest;
use App\Models\Book;
use App\Models\ReadingPlan;
use Illuminate\Http\Request;

/**
 * 読書計画の登録・更新・読了・削除を管理するコントローラー。
 *
 * ログインユーザー自身の読書計画のみを表示し、
 * Policyを利用して編集・削除などの認可を行う。
 */
class ReadingPlanController extends Controller
{
    /**
     * ログインユーザーの読書計画一覧を表示する。
     *
     * 指定されたステータスによる絞り込みにも対応する。
     */
    public function index(Request $request)
    {
        $currentStatus = $request->query('status');

        $readingPlans = ReadingPlan::with('book')
            ->where('user_id', $request->user()->id)
            ->when($currentStatus, function ($query, $currentStatus) {
                $query->where('status', $currentStatus);
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('reading-plans.index', compact(
            'readingPlans',
            'currentStatus'
        ));
    }

    /**
     * 読書計画の新規作成画面を表示する。
     *
     * 計画へ登録できる書籍一覧を取得して画面へ渡す。
     */
    public function create()
    {
        $books = Book::orderBy('title')->get();

        return view('reading-plans.create', compact('books'));
    }

    /**
     * 新しい読書計画を登録する。
     *
     * 同じユーザー・同じ書籍で進行中の読書計画が存在する場合は、
     * 重複登録を防止してエラーメッセージを返す。
     */
    public function store(StoreReadingPlanRequest $request)
    {
        $validated = $request->validated();

        $alreadyExists = ReadingPlan::where('user_id', $request->user()->id)
            ->where('book_id', $validated['book_id'])
            ->where('status', ReadingPlanStatus::InProgress)
            ->exists();

        if ($alreadyExists) {
            return back()
                ->withErrors([
                    'book_id' => 'この書籍は既に進行中の読書計画が存在します。',
                ])
                ->withInput();
        }

        ReadingPlan::create([
            'user_id' => $request->user()->id,
            'book_id' => $validated['book_id'],
            'target_date' => $validated['target_date'],
            'status' => ReadingPlanStatus::InProgress,
        ]);

        return redirect()
            ->route('reading-plans.index')
            ->with('success', '読書計画を作成しました。');
    }

    /**
     * 読書計画の編集画面を表示する。
     *
     * Policyにより、計画の所有者だけが編集できる。
     *
     * @param  ReadingPlan  $readingPlan  編集対象の読書計画
     */
    public function edit(ReadingPlan $readingPlan)
    {
        $this->authorize('update', $readingPlan);

        return view('reading-plans.edit', compact('readingPlan'));
    }

    /**
     * 読書計画の期日を更新する。
     *
     * Policyにより、計画の所有者だけが更新できる。
     *
     * @param  UpdateReadingPlanRequest  $request  検証済みの更新データ
     * @param  ReadingPlan  $readingPlan  更新対象の読書計画
     */
    public function update(UpdateReadingPlanRequest $request, ReadingPlan $readingPlan)
    {
        $this->authorize('update', $readingPlan);

        $readingPlan->update($request->validated());

        return redirect()
            ->route('reading-plans.index')
            ->with('success', '読書計画を更新しました。');
    }

    /**
     * 指定された読書計画を削除する。
     *
     * Policyにより、計画の所有者だけが削除できる。
     *
     * @param  ReadingPlan  $readingPlan  削除対象の読書計画
     */
    public function destroy(ReadingPlan $readingPlan)
    {
        $this->authorize('delete', $readingPlan);

        $readingPlan->delete();

        return redirect()
            ->route('reading-plans.index')
            ->with('success', '読書計画を削除しました。');
    }

    /**
     * 読書計画を読了状態へ変更する。
     *
     * ステータスを読了へ更新し、操作時の日時を読了日時として登録する。
     *
     * 進行中・期限切れのどちらの状態からでも読了へ変更できる。
     *
     * @param  ReadingPlan  $readingPlan  読了対象の読書計画
     */
    public function complete(ReadingPlan $readingPlan)
    {
        $this->authorize('complete', $readingPlan);

        // 読了操作を行った日時を読了日時として記録する
        $readingPlan->update([
            'status' => ReadingPlanStatus::Completed,
            'completed_at' => now(),
        ]);

        return redirect()
            ->route('reading-plans.index')
            ->with('success', '読書計画を完了しました。');
    }
}
