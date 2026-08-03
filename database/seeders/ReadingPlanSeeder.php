<?php

namespace Database\Seeders;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ReadingPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::first();
        $books = Book::take(5)->get();

        if (! $user || $books->count() < 5) {
            return;
        }

        // 3日前通知対象(進行中)
        ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $books[0]->id,
            'target_date' => Carbon::today()->addDays(3),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        // 当日通知対象(進行中)
        ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $books[1]->id,
            'target_date' => Carbon::today(),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        // 3日後通知対象(期日切れ)
        ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $books[2]->id,
            'target_date' => Carbon::today()->subDays(3),
            'status' => ReadingPlanStatus::Overdue,
        ]);

        // 期日切れデータ
        ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $books[3]->id,
            'target_date' => Carbon::today()->subDays(5),
            'status' => ReadingPlanStatus::Overdue,
        ]);

        // 読了データ
        ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $books[4]->id,
            'target_date' => Carbon::today()->subDays(7),
            'completed_at' => Carbon::today()->subDays(6),
            'status' => ReadingPlanStatus::Completed,
        ]);
    }
}
