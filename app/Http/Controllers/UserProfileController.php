<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class UserProfileController extends Controller
{
    public function updateProfile(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string'],
            'phone' => ['required', 'string'],
            'profile_picture' => ['sometimes', 'image'],
        ]);
        
        $file = $request->file('profile_picture');
        $fileUploaded = false;

        if ($file && $file->isValid()) {
            $fileUploaded = Storage::disk('public')->put('', $file);
            
            if (!$fileUploaded) {
                return response(['Photo file could not be uploaded'], Response::HTTP_BAD_REQUEST);
            }
        }
        
        $fileDiskStorageTitle = $fileUploaded;

        $userData = [
            'name' => $request->input('name'),
            'phone' => $request->input('phone')
        ];

        if ($fileUploaded) {
            $userData['profile_picture'] = $fileDiskStorageTitle;
        }
        
        $user = Auth::user();
        $existingPhotoFilename = $user->getAttributes()['profile_picture'];
        $updated = $user->update($userData);
        
        if (!$updated) {
            return response('Could not update profile', Response::HTTP_BAD_REQUEST);
        }

        
        if ($fileUploaded && $existingPhotoFilename) {
        // TODO: Delete Existing photo, assuming there was one, in filesystem now that we've uploaded and saved new photo

            $deletedPhoto = Storage::disk('public')->delete($existingPhotoFilename);

            if (!$deletedPhoto) {
                throw new \Exception('Could not delete image file:' . $existingPhotoFilename);
            }
        }
        
        return response($user->only('id', 'name', 'email', 'photoUrl', 'phone'), 200);
    }
    
    public function user()
    {
        if (Auth::check()) {
            $user = Auth::user();
            $user->load('roles');
            $roles = $user->roles->pluck('name')->toArray();

            return 
                array_merge($user->only('id', 'name', 'email', 'photoUrl', 'phone'), ['isDoctor' => $user->isDoctor(), 'roles' => $roles])
            ;
        }

        return response()->json(['message' => 'Unauthorized'], 401);
    }
}
