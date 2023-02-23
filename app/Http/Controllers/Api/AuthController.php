<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AuthController extends Controller
{
     /**
     * Write code on Method
     *
     * @return response()
     */
    public function index()
    {
        return view('auth.login');
    }  
      
    /**
     * Write code on Method
     *
     * @return response()
     */
    public function registration()
    {
        return view('auth.registration');
    }
    public function verifyShow()
    {
        return view('verify');
    }
    
    /**
     * Write code on Method
     *
     * @return response()
     */
    public function postLogin(Request $request)
    {
        $request->validate([
            'email' => 'required',
            'password' => 'required',
        ]);
   
        $credentials = $request->only('email', 'password');
        $users = DB::table('users')->where('email','=', $request->email)->where('verified_user', '!=', '0')->get();

        if($users){
            if (Auth::attempt($credentials)) {
                return redirect()->intended('dashboard')
                            ->withSuccess('You have Successfully loggedin');
            }
      
            return redirect("login")->withSuccess('Oppes! You have entered invalid credentials');
        }else{
            return response('Please verify your email first', 402);
        }

      
    }

    public function sendEmail(Request $request)
    {
        $request->validate([
            'email' => 'required',
        ]);

        $fourRandomDigit = mt_rand(1000,9999);

        DB::table('users')
        ->where('email', $request->email)
        ->update(['verify_code' => $fourRandomDigit]);

        Mail::send('email.codeVerification', ['fourRandomDigit' => $fourRandomDigit], function($message) use($request){
            $message->to($request->email);
            $message->subject('Email Verification Mail');
        });

        return response('Sent', 200);
    }

    public function sendCode(Request $request)
    {

        $users = DB::table('users')->where('email','=', $request->email)->where('verify_code', '=', $request->code)->get();

        if($users){
            DB::table('users')
            ->where('email', $request->email)
            ->update(['verified_user' => '1']);

            return response('User verified successfully', 204);

        }else{

            return response('Something went wrong', 204);
        }
    
    }
    
    /**
     * Write code on Method
     *
     * @return response()
     */
    public function postRegistration(Request $request)
    {  
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
        ]);
           
        $data = $request->post();
        $createUser = $this->create($data);
  
        $token = Str::random(64);

        // UserVerify::create([
        //       'user_id' => $createUser->id, 
        //       'token' => $token
        //     ]);

            $verifyr = new UserVerify;
            $verifyr->user_id = $createUser->id;
            $verifyr->token = $token;
            $verifyr->created_at = date('Y-m-d H:i:s');
            $verifyr->updated_at = date('Y-m-d H:i:s');
            $verifyr->save();

        Mail::send('email.emailVerificationEmail', ['token' => $token], function($message) use($request){
              $message->to($request->email);
              $message->subject('Email Verification Mail');
          });
         
        return redirect("dashboard")->withSuccess('Great! You have Successfully loggedin');
    }
    
    /**
     * Write code on Method
     *
     * @return response()
     */
    public function dashboard()
    {
        if(Auth::check()){
            return view('dashboard');
        }
  
        return redirect("login")->withSuccess('Opps! You do not have access');
    }
    
    /**
     * Write code on Method
     *
     * @return response()
     */
    public function create(array $data)
    {
      return User::create([
        'name' => $data['name'],
        'email' => $data['email'],
        'password' => Hash::make($data['password'])
      ]);
    }
      
    /**
     * Write code on Method
     *
     * @return response()
     */
    public function logout() {
        Session::flush();
        Auth::logout();
  
        return Redirect('login');
    }
    /**
     * Write code on Method
     *
     * @return response()
     */
    public function verifyAccount($token)
    {
        $verifyUser = UserVerify::where('token', $token)->first();
  
        $message = 'Sorry your email cannot be identified.';
  
        if(!is_null($verifyUser) ){
            $user = $verifyUser->user;
              
            if(!$user->email_verified_at) {
                $verifyUser->user->email_verified_at = 1;
                $verifyUser->user->save();
                $message = "Your e-mail is verified. You can now login.";
            } else {
                $message = "Your e-mail is already verified. You can now login.";
            }
        }
  
      return redirect()->route('login')->with('message', $message);
    }
}
