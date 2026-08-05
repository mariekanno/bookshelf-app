<?php

namespace App\Http\Controllers;

use App\Enums\ReadingPlanStatus;
use App\Http\Requests\StoreReadingPlanRequest;
use App\Http\Requests\UpdateReadingPlanRequest;
use App\Models\Book;
use App\Models\ReadingPlan;
use Illuminate\Http\Request;

class ReadingPlanController extends Controller
{
    /**
     * Display a listing of the resource.
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
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $books = Book::orderBy('title')->get();

        return view('reading-plans.create', compact('books'));
    }

    /**
     * Store a newly created resource in storage.
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
     * Show the form for editing the specified resource.
     */
    public function edit(ReadingPlan $readingPlan)
    {
        $this->authorize('update', $readingPlan);

        return view('reading-plans.edit', compact('readingPlan'));
    }

    /**
     * Update the specified resource in storage.
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
     * Remove the specified resource from storage.
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
     * Remove the specified resource from storage.
     */
    public function complete(ReadingPlan $readingPlan)
    {
        $this->authorize('complete', $readingPlan);

        $readingPlan->update([
            'status' => ReadingPlanStatus::Completed,
            'completed_at' => now(),
        ]);

        return redirect()
            ->route('reading-plans.index')
            ->with('success', '読書計画を完了しました。');
    }
}
