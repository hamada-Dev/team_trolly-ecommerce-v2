<?php

namespace App\Http\Controllers;

use App\Models\Newsletter;
use Illuminate\Http\Request;

class NewsletterController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // if(auth()->user()->isAbleTo('Manage Newsletter'))
        // {
            $newsletters = Newsletter::where('theme_id',APP_THEME())->where('store_id',getCurrentStore())->get();
            return view('newsletter.index', compact('newsletters'));
        // }
        // else
        // {
        //     return redirect()->back()->with('error', __('Permission denied.'));
        // }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = \Validator::make(
            $request->all(),
            [
                'email' => ['required','unique:newsletters'],

            ]
        );
        if($validator->fails())
        {
            $messages = $validator->getMessageBag();
            return redirect()->back()->with('error', $messages->first());
        }

        $newsletter                 = new Newsletter();
        $newsletter->email         = $request->email;
        if(\Auth::user())
        {
            $newsletter->customer_id         = \Auth::user()->id;
        }
        else{
            $newsletter->customer_id         = '0';
        }
        $newsletter->theme_id       = APP_THEME();
        $newsletter->store_id       = getCurrentStore();
        $newsletter->save();

        return redirect()->back()->with('success', __('Newsletter successfully subscribe.'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Newsletter $newsletter)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Newsletter $newsletter)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Newsletter $newsletter)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Newsletter $newsletter)
    {
        // if(auth()->user()->isAbleTo('Delete Newsletter'))
        // {
            $newsletter->delete();
            return redirect()->back()->with('success', __('Email Newsletter delete successfully.'));
        // }
        // else
        // {
        //     return redirect()->back()->with('error', __('Permission denied.'));
        // }
    }
}
