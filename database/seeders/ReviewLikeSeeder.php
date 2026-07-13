<?php

namespace Database\Seeders;

use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewLikeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();

        Review::all()->each(function (Review $review) use ($users) {
            $likeCount = random_int(0, 3);

            $userIds = $users
                ->where('id', '!=', $review->user_id)
                ->shuffle()
                ->take($likeCount)
                ->pluck('id')
                ->all();

            foreach ($userIds as $userId) {
                $user = $users->firstWhere('id', $userId);

                $user->likedReviews()
                    ->syncWithoutDetaching([$review->id]);
            }
        });
    }
}
