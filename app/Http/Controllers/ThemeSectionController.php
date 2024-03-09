<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Themes\{ThemeSection, ThemeSectionDraft};
use Qirolab\Theme\Theme;

class ThemeSectionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        if (isset($request->theme_id)) {
            $theme_id = $request->theme_id;
        } else {
            $theme_id = Theme::active();
        }
        return view('theme_preview.section_create', compact('theme_id'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = \Validator::make(
            $request->all(),
            [
                'section_name' => 'required',
            ]
        );

        if($validator->fails())
        {
            $messages = $validator->getMessageBag();
            return redirect()->back()->with('error', $messages->first());
        }

        $section_name = strtolower(str_replace(' ', '_',$request->section_name));
        $themeSectionQuery = ThemeSection::where('store_id', getCurrentStore())->where('theme_id', $request->theme_id);
        $themeSectionDraftQuery = ThemeSectionDraft::where('store_id', getCurrentStore())->where('theme_id', $request->theme_id);
        $exist = (clone $themeSectionQuery)->where('section_name', $section_name)->first();
        if ($exist) {
            return redirect()->back()->with('error', 'Theme section already added.');
        }
        $last_section = (clone $themeSectionQuery)->orderBy('order', 'DESC')->first();
        if ($last_section) {
            (clone $themeSectionQuery)->create([
                'section_name' => $section_name,
                'order' => $last_section->order + 1,
                'is_hide' => 0,
                'store_id' => getCurrentStore(),
                'theme_id' => $request->theme_id,
            ]);
            // create draft record
            (clone $themeSectionDraftQuery)->create([
                'section_name' => $section_name,
                'order' => $last_section->order + 1,
                'is_hide' => 0,
                'store_id' => getCurrentStore(),
                'theme_id' => $request->theme_id,
            ]);
        } else {
            (clone $themeSectionQuery)->create([
                'section_name' => $section_name,
                'order' => 0,
                'is_hide' => 0,
                'store_id' => getCurrentStore(),
                'theme_id' => $request->theme_id,
            ]);
            // create draft record
            (clone $themeSectionDraftQuery)->create([
                'section_name' => $section_name,
                'order' => 0,
                'is_hide' => 0,
                'store_id' => getCurrentStore(),
                'theme_id' => $request->theme_id,
            ]);
        }

        return redirect()->back()->with('success', 'Theme section added successfully');
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        //
    }
}
