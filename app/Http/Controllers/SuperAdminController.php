<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\Course;
use App\Models\User;
use App\Models\Subject;

class SuperAdminController extends Controller
{
    // Return dashboard view
    public function dashboard() {
        return view('superadmin.dashboard');
    }

    // Get all users
    public function get_users() {
        $users = User::all();

        return response()->json([
            'data' => $users
        ]);
    }

    // Get single user
    public function get_user($user_id) {
        $user = User::findOrFail($user_id);

        return response()->json([
            'user' => $user
        ]);
    }

    // Edit user
    public function edit_user(Request $request, $user_id) {
        // Validate data
        $data = $request->validate([
            'edit_first_name'    =>  'required',
            'edit_middle_name'   =>  '',
            'edit_last_name'     =>  'required',
            'edit_suffix'        =>  '',
            'edit_email'         =>  'required|unique:users,email,'.$user_id,
            'edit_username'      =>  'required',
            'edit_role'          =>  'required',
        ]);

        $user = User::findOrFail($user_id);

        $user->first_name = $data['edit_first_name'];
        $user->middle_name = $data['edit_middle_name'];
        $user->last_name = $data['edit_last_name'];
        $user->suffix = $data['edit_suffix'];
        $user->email = $data['edit_email'];
        $user->username = $data['edit_username'];
        $user->role = $data['edit_role'];

        if($user->save()) {
            return response()->json([
                'message' => 'Successfully updated.'
            ], 200);
        } else {
            return response()->json([
                'message' => 'Something went wrong.'
            ], 500);
        }
    }

    // Add user
    public function add_user(Request $request) {
        // Validate inputs
        $data = $request->validate([
            'first_name'    =>  'required',
            'middle_name'   =>  '',
            'last_name'     =>  'required',
            'suffix'        =>  '',
            'email'         =>  'required|unique:users,email',
            'username'      =>  'required',
            'password'      =>  'required',
            'role'          =>  'required',
        ]);

        if(User::create($data)) {
            return response()->json([
                'message' => 'Successfully added.'
            ], 200);
        }
    }

    // Delete User
    public function delete_user($user_id) {
        $user = User::findOrFail($user_id);
        if($user->delete()) {
            return response()->json(['message' => "Record has been deleted successfully"], 200);
        } else {
            return response()->json(['message' => "An error has been encountered."], 500);
        }
    }

    // Add Courses view - return view
    public function courses(Request $request) {
        $chairpersons   = User::where('role', 2)->get(); // For new courses modal select option
        $deans          = User::where('role', 3)->get();

        return view('superadmin/courses')->with([
            'chairpersons'  => $chairpersons,
            'deans'         => $deans
        ]);
    }

    // Fetch all courses - return json
    public function courses_table() {
        $courses = Course::with('chairperson')->get();

        return response()->json(['data' => $courses]);
    }

    // Process new course to save to database - process
    public function save_course(Request $request) {
        // Validate inputs
        $course_data = $request->validate([
            'course_name'   => ['required'],
            'course_code'   => ['required'],
            'chairperson'   => ['exists:users,id'],
            'dean'          => ['required']
        ]);

        // Save new course to database
        $course = new Course();
        $course->course_name    = $course_data['course_name'];
        $course->course_code    = $course_data['course_code'];
        $course->chairperson_id = $course_data['chairperson'];
        $course->dean_id        = $course_data['dean'];
        $course->save();

        // Refresh page
        return redirect()->route('superadmin.courses_view');
    }

    // Subject page - return view
    public function course_detail($id) {
        $course = Course::where('id', $id)->first();
        $chairpersons = User::where('role', 2)->get();

        return view('superadmin.course_detail')->with([
            'course' => $course,
            'chairpersons' => $chairpersons
        ]);
    }

    // Retrieve data for edit - return json
    public function get_course($id) {
        $course = Course::findOrFail($id);

        return response()->json(['course' => $course], 200);
    }

    // Update course details - process
    public function update_course(Request $request, $id) {
        // Validate inputs
        $course_data = $request->validate([
            'edit_course_name' => ['required'],
            'edit_course_code' => ['required'],
            'edit_chairperson' => ['exists:users,id']
        ]);

        // Find the existing record
        $course = Course::findOrFail($id);

        // Update the values and save
        $course->course_name = $course_data['edit_course_name'];
        $course->course_code = $course_data['edit_course_code'];
        $course->chairperson_id = $course_data['edit_chairperson'];
        $course->save();

        // Refresh page
        return redirect()->route('superadmin.courses_view');
    }

    // Delete course - process
    public function delete_course($id) {
        $course = Course::findOrFail($id);
        if($course->delete()) {
            return response()->json(['message' => "Record has been deleted successfully"], 200);
        } else {
            return response()->json(['message' => "An error has been encountered."], 500);
        }
    }

    // Get list of subjects per course - return json
    public function get_subjects($id) {
        $subjects = Subject::where('course_id', $id)->get();

        return response()->json(['data' => $subjects]);
    }

    // Save subject - process
    public function save_subject(Request $request) {
        // dd($request->input());

        // Validate inputs
        $subject_data = $request->validate([
            'subject_code' => ['required'],
            'description' => ['required'],
            'unit' => ['numeric'],
            'course_id' => ['required'],
            'chairperson' => ['required'],
        ]);

        // Initialize the subject model
        $subject = new Subject();
        $subject->subject_code = $subject_data['subject_code'];
        $subject->subject_description = $subject_data['description'];
        $subject->unit = $subject_data['unit'];
        $subject->course_id = $subject_data['course_id'];
        $subject->chairperson_id = $subject_data['chairperson'];
        $subject->save();

        // Refresh page
        return redirect()->route('superadmin.course_detail', $subject_data['course_id']);
    }

    // Get subject - return json
    public function get_subject($id) {
        $subject = Subject::findOrFail($id);

        return response()->json(['subject' => $subject], 200);
    }

    // Update subject details - process
    public function update_subject(Request $request, $id) {
        // Validate inputs
        $subject_data = $request->validate([
            'edit_subject_code' => ['required'],
            'edit_description' => ['required'],
            'edit_unit' => ['numeric'],
            'course_id' => ['required'],
            'edit_chairperson' => ['required']
        ]);

        // Find the existing record - return view
        $subject = Subject::findOrFail($id);

        // Update the values and save
        $subject->subject_code = $subject_data['edit_subject_code'];
        $subject->subject_description = $subject_data['edit_description'];
        $subject->unit = $subject_data['edit_unit'];
        $subject->chairperson_id = $subject_data['edit_chairperson'];
        $subject->save();

        // Refresh page
        return redirect()->route('superadmin.course_detail', $subject_data['course_id']);
    }

    // Delete subject - process
    public function delete_subject($id) {
        $subject = Subject::findOrFail($id);
        if($subject->delete()) {
            return response()->json(['message' => "Record has been deleted successfully"], 200);
        } else {
            return response()->json(['message' => "An error has been encountered."], 500);
        }
    }

}
