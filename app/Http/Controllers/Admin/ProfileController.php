<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function edit()
    {
        $user = auth()->user();
        return view('admin.profile.edit', compact('user'));
    }

    public function update(Request $request)
    {
        $user = auth()->user();
        $request->validate([
            'name'             => 'required|string|max:255',
            'email'            => 'required|email|unique:users,email,'.$user->id,
            'phone'            => 'nullable|string|max:20',
            'address'          => 'nullable|string',
            'facebook'         => 'nullable|url',
            'twitter'          => 'nullable|url',
            'linkedin'         => 'nullable|url',
            'instagram'        => 'nullable|url',
            'website'          => 'nullable|url',
            'profile_pic'      => 'nullable|image|max:2048',
            'background_pic'   => 'nullable|image|max:5120',
            'current_password' => 'nullable|required_with:password',
            'password'         => 'nullable|min:8|confirmed',
        ]);

        $data = $request->only('name','email','phone','address','facebook','twitter','linkedin','instagram','website');

        if ($request->hasFile('profile_pic')) {
            if ($user->profile_pic) Storage::disk('public')->delete($user->profile_pic);
            $data['profile_pic'] = $request->file('profile_pic')->store('profiles', 'public');
        }

        if ($request->hasFile('background_pic')) {
            if ($user->background_pic) Storage::disk('public')->delete($user->background_pic);
            $data['background_pic'] = $request->file('background_pic')->store('backgrounds', 'public');
        }

        if ($request->password) {
            if (!Hash::check($request->current_password, $user->password)) {
                return back()->withErrors(['current_password' => 'Current password is incorrect.']);
            }
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);
        AuditLog::log('updated', ['model' => get_class($user), 'model_id' => $user->id, 'description' => 'Profile updated']);

        return back()->with('success', 'Profile updated successfully!');
    }
}
