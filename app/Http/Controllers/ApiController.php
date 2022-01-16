<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Http;
use Validator;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\News;
use App\Helpers\FileSystem;

class ApiController extends Controller
{
    public function login(Request $request)
    {
        $rules = [
            'email' => 'required|email|max:255',
            'password' => 'required|min:6|max:255',
        ];
        
        $validator = Validator::make($request->all(), $rules);
        
        if ($validator->fails()) {
            return response()->json([
                'success'=> false,
                'key'=> 'error-validation'
            ]);
        }
        
        $id = User::select('id')
                ->where('email', '=', $request->get('email') )
                ->where('password', '=', makePasswordMd5($request->get('email')) )
                ->first();
        
        if( ! $id) {
            return response()->json([
                'success'=> false,
                'key'=> 'user-not-found'
            ]);
        }
        
        $api_token = generateApiKey();
        
        $v = new User();
        $v->where('email', '=', $request->get('email'))
        ->update(
            [
                'api_token' => $api_token,
                'expire_api_token' => time() + config("constants.EXPIRE_API_TOKEN_IN_SECOND")
            ]);
        
        return response()->json([
            'success'=> true,
            'api_token' => $api_token,
            'expire_api_token_in_sec' => config("constants.EXPIRE_API_TOKEN_IN_SECOND")
        ]);
    }
    
    public function registration(Request $request)
    {
        $rules = [
            'email' => 'required|email|max:255',
            'password' => 'required|min:6|max:255',
        ];
        
        $validator = Validator::make($request->all(), $rules);
        
        if ($validator->fails()) {
            return response()->json([
                'success'=> false,
                'key'=> 'error-validation'
            ]);
        }
        
        $id = User::select('id')
                ->where('email', '=', $request->get('email') )
                ->first();
        
        if( $id) {
            return response()->json([
                'success'=> false,
                'key'=> 'user-exist'
            ]);
        }
        
        $api_token = generateApiKey();
        
        $v = new User();
        $v->email             = $request->get('email');
        $v->password          = makePasswordMd5($request->get('email'));
        $v->api_token         = $api_token;
        $v->expire_api_token  = time() + config("constants.EXPIRE_API_TOKEN_IN_SECOND");
        $v->save();
        
        return response()->json([
            'success'=> true,
            'api_token' => $api_token,
            'expire_api_token_in_sec' => config("constants.EXPIRE_API_TOKEN_IN_SECOND")
        ]);
    }
    
    public function saveNews(Request $request)
    {
        // Validation
        $rules = [
            'id' => 'required',
            'img' => 'required',
            'title' => 'required|max:255',
            'descr' => 'required',
            'date_time' => 'required',
        ];
        
        $validator = Validator::make($request->all(), $rules);
        
        if ($validator->fails()) {
            var_dump($validator->errors()->first());exit;
            
            return response()->json([
                'success'=> false,
                'key'=> 'error-validation'
            ]);
        }
        
        $id = $request->get('id');
        
        $date_time = $request->get('date_time');
        
        if( ! preg_match('#\d{2}.\d{2}.\d{4}\s\d{2}:\d{2}:\d{2}#', $date_time) ) {
            return response()->json([
                'success'=> false,
                'key'=> 'error-validation'
            ]);
        }
        
        $dateTimePublic = \DateTime::createFromFormat("d.m.Y H:i:s", $date_time, new \DateTimeZone('Europe/Moscow'));
        
        if (! ($dateTimePublic instanceof \DateTime)) {
            
            return response()->json([
                'success'=> false,
                'key'=> 'unknow-error'
            ]);
        }
        
        if($id === 'null') {
            
            $file = $request->file('img');
            $mime_type = $file->getClientMimeType();
            
            if( ! (    $mime_type === 'image/png'
                    || $mime_type === 'image/jpeg'
                    || $mime_type === 'image/webp'
                ) 
            ) {
                return response()->json([
                    'success'=> false,
                    'key'=> 'error-validation'
                ]);
            }

            $filename = microtime(true).'.wepb';
            $tmpPathSave = base_path().'/private/tmp/'.$filename;
            $destinationPathSave = public_path('images').'/'.$filename;

            // Upload file
            $file->move(base_path().'/private/tmp/', $filename);

            list($width, $height, $type, $attr) = getimagesize($tmpPathSave);
            $new_image = g_get_new_scale_img_size(
                      $width
                    , $height
                    , ( isset($_POST['max_w']) && is_numeric($_POST['max_w']) ) ? (int) $_POST['max_w'] : 1024
                    , ( isset($_POST['max_h']) && is_numeric($_POST['max_h']) ) ? (int) $_POST['max_h'] : 1024
                );

            $src = null;
            switch ($mime_type) {
                case 'image/png':  $src = imagecreatefrompng($tmpPathSave); break;
                case 'image/jpeg': $src = imagecreatefromjpeg($tmpPathSave); break;
                case 'image/webp': $src = imagecreatefromwebp($tmpPathSave); break;
            }
                
            $dst = imagecreatetruecolor($new_image[0], $new_image[1]);
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $new_image[0], $new_image[1], $width, $height);

            imagewebp($dst, $destinationPathSave);

            @unlink($tmpPathSave);

            $v = new News();
            $v->user_id              = $request->get('account_id');
            $v->title                = $request->get('title');
            $v->description          = $request->get('descr');
            $v->main_img             = $filename;
            $v->tms_datetime_public  = $dateTimePublic->getTimestamp();
            $v->save();
        }
        else {
            
            $file = $request->file('img');
            
            
            if($file != null) {
                $mime_type = $file->getClientMimeType();
                
                if( ! (    $mime_type === 'image/png'
                        || $mime_type === 'image/jpeg'
                        || $mime_type === 'image/webp'
                    ) 
                ) {
                    return response()->json([
                        'success'=> false,
                        'key'=> 'error-validation'
                    ]);
                }
                
                $row = News::select('main_img')
                        ->where('id', '=', $id )
                        ->where('user_id', '=', $request->get('account_id'))
                        ->first()->toArray();
                
                @unlink(public_path('images').'/'.$row['main_img']);
                
                $filename = microtime(true).'.wepb';
                $tmpPathSave = base_path().'/private/tmp/'.$filename;
                $destinationPathSave = public_path('images').'/'.$filename;

                // Upload file
                $file->move(base_path().'/private/tmp/', $filename);

                list($width, $height, $type, $attr) = getimagesize($tmpPathSave);
                $new_image = g_get_new_scale_img_size(
                          $width
                        , $height
                        , ( isset($_POST['max_w']) && is_numeric($_POST['max_w']) ) ? (int) $_POST['max_w'] : 1024
                        , ( isset($_POST['max_h']) && is_numeric($_POST['max_h']) ) ? (int) $_POST['max_h'] : 1024
                    );

                $src = null;
                switch ($mime_type) {
                    case 'image/png':  $src = imagecreatefrompng($tmpPathSave); break;
                    case 'image/jpeg': $src = imagecreatefromjpeg($tmpPathSave); break;
                    case 'image/webp': $src = imagecreatefromwebp($tmpPathSave); break;
                }
                
                $dst = imagecreatetruecolor($new_image[0], $new_image[1]);
                imagecopyresampled($dst, $src, 0, 0, 0, 0, $new_image[0], $new_image[1], $width, $height);

                imagewebp($dst, $destinationPathSave);

                @unlink($tmpPathSave);
                
                $v = new News();
                $v->where('id', '=', $id)
                ->where('user_id', '=', $request->get('account_id'))
                ->update(
                    [
                        'main_img' => $filename
                    ]);
            }
            
            $v = new News();
            $v->where('id', '=', $id)
            ->where('user_id', '=', $request->get('account_id'))
            ->update(
                [
                    'title'               => $request->get('title'),
                    'description'         => $request->get('descr'),
                    'tms_datetime_public' => $dateTimePublic->getTimestamp()
                ]);
        }
        
        return response()->json([
            'success'=> true,
        ]);
    }
    
    public function adminGetNews(Request $request)
    {
        $list = News::where('user_id', '=', $request->get('account_id'))
                ->orderByDesc('tms_datetime_public')
                ->get()
                ->toArray();
        
        foreach($list as $k=>$v) {
            $list[$k]['created_at'] = strtotime($list[$k]['created_at']);
        }
        
        return response()->json([
            'success'=> true,
            'list' => $list
        ]);
    }
    
    public function removeNews(Request $request)
    {
        // Validation
        $rules = [
            'id' => 'required|numeric',
        ];
        
        $validator = Validator::make($request->all(), $rules);
        
        if ($validator->fails()) {
            return response()->json([
                'success'=> false,
                'key'=> 'error-validation'
            ]);
        }
        
        if( ! News::where('id', '=', $request->get('id') )
                ->where('user_id', '=', $request->get('account_id'))
                ->first() )
        {
            return response()->json([
                'success'=> false,
                'key'=> 'access-error'
            ]);
        }
        
        $row = News::select('main_img')->where('id', '=', $request->get('id') )->first()->toArray();
        
        if(file_exists(public_path('images').'/'.$row['main_img'])) {
            unlink(public_path('images').'/'.$row['main_img']);
        }
        
        News::where('id', $request->get('id'))->delete();
        
        return response()->json([
            'success'=> true
        ]);
    }
    
    public function getNews(Request $request)
    {
        $list = News::select('id', 'title', 'main_img', 'tms_datetime_public')
                ->where('tms_datetime_public', '<', time())
                ->orderByDesc('tms_datetime_public')
                ->get()
                ->toArray();
        
        return response()->json([
            'success'=> true,
            'list' => $list
        ]);
    }
    public function getOneNews(Request $request, $id)
    {
        $id         = (int) $id;
        
        $row = News::select('title', 'description', 'main_img', 'tms_datetime_public', 'user_id')
                ->where('id', '=', $id )
                ->first()->toArray();
        
        $user_info = User::select('email')
                ->where('id', '=', $row['user_id'] )
                ->first()->toArray();
        
        unset($row['user_id']);
        
        return response()->json([
            'success' => true,
            'row' => $row,
            'user_info' => $user_info,
        ]);
    }
    
}