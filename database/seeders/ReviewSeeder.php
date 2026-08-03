<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $books = Book::all();

        $comments = [
            'とても読みやすく、内容も分かりやすかったです。',
            '多くの学びがあり、何度も読み返したいと思いました。',
            '具体例が豊富で、理解しやすい内容でした。',
            '考え方が大きく変わるきっかけになりました。',
            '興味深い内容で、最後まで楽しんで読めました。',
            '初心者にも分かりやすく、おすすめできる一冊です。',
            '実生活や仕事に活かせる内容が多かったです。',
            '少し難しい部分もありましたが、勉強になりました。',
            '期待していた以上に充実した内容でした。',
            '文章が読みやすく、内容にも引き込まれました。',
            '新しい知識を得られて、とても満足しました。',
        ];

        foreach ($books as $bookIndex => $book) {
            // 書籍ごとに2〜4人のユーザーを選ぶ
            $reviewUsers = $users
                ->shuffle()
                ->take(($bookIndex % 3) + 2);

            foreach ($reviewUsers as $userIndex => $user) {
                Review::create([
                    'user_id' => $user->id,
                    'book_id' => $book->id,
                    'rating' => (($bookIndex + $userIndex) % 5) + 1,
                    'comment' => $comments[$bookIndex],
                ]);
            }
        }
    }
}
