<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\User;
use App\Rules\ValidPhone;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ContactMessageController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => ['required', 'string', new ValidPhone],
            'subject' => 'nullable|string|max:255',
            'message' => 'required|string',
        ]);

        ContactMessage::create($validated);

        return back()->with('success', 'Message sent successfully!');
    }

    /**
     * Display a listing of the resource (Admin).
     */
    public function index()
    {
        if (auth()->user()->role_id != User::ROLE_ADMIN) {
            abort(403);
        }

        $messages = ContactMessage::latest()->paginate(10);

        return Inertia::render('Admin/ContactMessages/Index', [
            'messages' => $messages
        ]);
    }

    /**
     * Update the specified resource in storage (Admin).
     */
    public function update(Request $request, ContactMessage $contactMessage)
    {
        if (auth()->user()->role_id != User::ROLE_ADMIN) {
            abort(403);
        }

        $validated = $request->validate([
            'status' => 'required|string|in:new,read,replied',
        ]);

        $contactMessage->update($validated);

        return back()->with('success', 'Message updated successfully!');
    }

    /**
     * Remove the specified resource from storage (Admin).
     */
    public function destroy(ContactMessage $contactMessage)
    {
        if (auth()->user()->role_id != User::ROLE_ADMIN) {
            abort(403);
        }

        $contactMessage->delete();

        return back()->with('success', 'Message deleted successfully!');
    }
}
