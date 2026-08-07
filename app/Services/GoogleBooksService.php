<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

/**
 * Google Books APIとの通信を行うサービスクラス
 *
 * ISBNをもとに書籍情報を取得し、
 * アプリで利用しやすい形式に整形して返す。
 */
class GoogleBooksService
{
    /**
     * ISBNから書籍情報を取得する
     *
     * Google Books APIへリクエストを送り、
     * タイトル・著者・出版日・説明・画像URLを取得する。
     * 該当する書籍が存在しない場合や通信エラーの場合はnullを返す。
     *
     * @param  string  $isbn  ISBNコード
     * @return array|null 書籍情報、取得できない場合はnull
     */
    public function searchByIsbn(string $isbn): ?array
    {
        try {
            // Google Books APIへリクエストを送信
            $response = Http::get(
                'https://www.googleapis.com/books/v1/volumes',
                [
                    'q' => 'isbn:'.$isbn,
                    'key' => config('services.google_books.api_key'),
                ]
            );
        } catch (ConnectionException) {
            return null;
        }

        // API通信に失敗した場合はnullを返す
        if ($response->failed()) {
            return null;
        }

        // レスポンスから書籍情報を取得
        $volumeInfo = $response->json('items.0.volumeInfo');

        if (! $volumeInfo) {
            return null;
        }

        // アプリで利用する形式に整形して返す
        return [
            'title' => $volumeInfo['title'] ?? '',
            'author' => implode(', ', $volumeInfo['authors'] ?? []),
            'published_date' => $volumeInfo['publishedDate'] ?? null,
            'description' => $volumeInfo['description'] ?? '',
            'image_url' => $volumeInfo['imageLinks']['thumbnail'] ?? '',
        ];
    }
}
