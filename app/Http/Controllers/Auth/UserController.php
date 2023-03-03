<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    /**
     * @queryParam User List
     */
    public function userProfile(Request $request)
    {
        $users = DB::table('users')->get();

        if (!$users) {

            return response()->json([
                'success' => false,
                'message' => 'Data Not Found!',
            ], 404);

        } else {
            return response([
                'success' => true,
                'data' => $users,
                'message' => 'Data saved successfully'
            ], 200);
        }
    }
}
