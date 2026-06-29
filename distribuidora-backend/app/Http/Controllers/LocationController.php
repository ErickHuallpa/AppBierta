<?php

namespace App\Http\Controllers;

use App\Models\Location;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user() ?? \App\Models\User::first();
        $locations = Location::where('user_id', $user->id)->get();
        return response()->json($locations);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $user = $request->user() ?? \App\Models\User::first();

        $isDefault = Location::where('user_id', $user->id)->count() == 0;

        $location = Location::create([
            'user_id' => $user->id,
            'name' => $request->name,
            'address' => $request->address,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'is_default' => $isDefault,
        ]);

        return response()->json($location, 201);
    }

    public function update(Request $request, $id)
    {
        $user = $request->user() ?? \App\Models\User::first();
        $location = Location::where('user_id', $user->id)->findOrFail($id);
        
        $location->update($request->only(['name', 'address', 'latitude', 'longitude']));
        return response()->json($location);
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user() ?? \App\Models\User::first();
        $location = Location::where('user_id', $user->id)->findOrFail($id);
        
        $wasDefault = $location->is_default;
        $location->delete();

        // If it was default, make another one default if exists
        if ($wasDefault) {
            $newDefault = Location::where('user_id', $user->id)->first();
            if ($newDefault) {
                $newDefault->update(['is_default' => true]);
            }
        }

        return response()->json(['message' => 'Eliminado']);
    }

    public function setDefault(Request $request, $id)
    {
        $user = $request->user() ?? \App\Models\User::first();
        Location::where('user_id', $user->id)->update(['is_default' => false]);
        
        $location = Location::where('user_id', $user->id)->findOrFail($id);
        $location->update(['is_default' => true]);
        
        return response()->json($location);
    }
}
