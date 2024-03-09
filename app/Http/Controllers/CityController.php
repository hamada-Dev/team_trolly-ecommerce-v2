<?php

namespace App\Http\Controllers;
use App\Models\City;
use App\Models\State;
use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CityController extends Controller
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
    public function create()
    {
        //
        if (Auth::user()->type == 'super admin') {
            $countries = country::pluck('name','id');
            $state = state::pluck('name','id');

            return view('City.create',compact('countries','state'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $rules = [
            "country_id" => "required|exists:countries,id",
            "state_id" => "required|exists:states,id",
        ];
        foreach (config('translation.languages') as $locale => $index) {
            $rules += ['name.' . $locale => 'required|min:2|max:250'];
        }
        $validator = Validator::make($request->all(),$rules);
        if($validator->fails())
        {
            $messages = $validator->getMessageBag();
            return redirect()->back()->with('error', $messages->first());
        }
        $city = new City();
        $city->country_id = $request->country_id;
        $city->state_id = $request->state_id;
        $city->name = $request->name;
        $city->save();
        return redirect()->back()->with('success', __('city successfully created.'));
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */


    public function edit(City $city)
    {
        if (Auth::user()->type == 'super admin') {

            $countries = Country::all()->pluck('name', 'id');
            $country = Country::find($city->country_id);

            $states = State::where('country_id', $city->country_id)->pluck('name', 'id');
            $state = State::find($city->state_id);

            return view('City.edit', compact('city', 'countries', 'country', 'states', 'state'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, City $city)
    {
        $rules = [
            "country_id" => "required|exists:countries,id",
            "state_id" => "required|exists:states,id",
        ];
        foreach (config('translation.languages') as $locale => $index) {
            $rules += ['name.' . $locale => 'required|min:2|max:250'];
        }
        $validator = Validator::make($request->all(),$rules);
        if($validator->fails())
        {
            $messages = $validator->getMessageBag();
            return redirect()->back()->with('error', $messages->first());
        }
        $city->name = $request->name;
        $city->state_id =$request->state_id;
        $city->country_id = $request->country_id;
        $city->save();
        return redirect()->back()->with('success', __('city updated successfully.'));



    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(City $city)
    {
        if (Auth::user()->type == 'super admin') {
            $city->delete();
            return redirect()->back()->with('success', __('City delete successfully.'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }
}
