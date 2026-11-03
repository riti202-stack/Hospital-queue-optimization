<?php

namespace App\Http\Controllers;

use App\Models\LabTest;
use App\Models\TestRequestItem;
use Illuminate\Http\Request;

class AdminLabTestController extends Controller
{
    public function index()
    {
        $tests = LabTest::orderBy('category')->orderBy('name')->get()->groupBy('category');
        return view('admin.lab-tests.index', compact('tests'));
    }

    public function create()
    {
        return view('admin.lab-tests.form', ['test' => new LabTest(['is_active' => true, 'duration_minutes' => 15])]);
    }

    public function store(Request $request)
    {
        LabTest::create($this->validated($request));
        return redirect()->route('lab-tests.index')->with('success', 'Test added.');
    }

    public function edit(LabTest $labTest)
    {
        return view('admin.lab-tests.form', ['test' => $labTest]);
    }

    public function update(Request $request, LabTest $labTest)
    {
        $labTest->update($this->validated($request));
        return redirect()->route('lab-tests.index')->with('success', 'Test updated.');
    }

    public function destroy(LabTest $labTest)
    {
        // Tests already used in reports are hidden instead of deleted, so old reports stay correct
        if (TestRequestItem::where('lab_test_id', $labTest->id)->exists()) {
            $labTest->update(['is_active' => false]);
            return back()->with('success', 'This test is used in past reports, so it was hidden instead of deleted.');
        }

        $labTest->delete();
        return back()->with('success', 'Test deleted.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name'             => 'required|string|max:150',
            'category'         => 'required|string|max:100',
            'price'            => 'required|numeric|min:0',
            'room_no'          => 'nullable|string|max:50',
            'duration_minutes' => 'nullable|integer|min:5|max:240',
            'preparation'      => 'nullable|string|max:255',
            'description'      => 'nullable|string|max:1000',
        ]);
        $data['is_active'] = $request->boolean('is_active');
        $data['duration_minutes'] = $data['duration_minutes'] ?? 15;

        return $data;
    }
}