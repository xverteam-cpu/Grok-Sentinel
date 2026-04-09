<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ValidationApprovalController extends Controller
{
    public function index(): Response
    {
        $users = User::where('is_admin', false)
            ->where('validation_status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get(['id', 'name', 'email', 'created_at', 'validation_status']);

        return Inertia::render('Admin/ValidationApproval', [
            'users' => $users,
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'action' => 'required|in:approve,decline',
        ]);

        if ($user->is_admin) {
            abort(403);
        }

        $user->validation_status = $request->action === 'approve' ? 'approved' : 'declined';
        $user->save();

        return back();
    }
}
