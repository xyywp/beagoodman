<?php

namespace App\Http\Controllers;

use App\Lib\AliYunOss;
use Illuminate\Http\Request;
use App\Lib\FileUpload;
use App\Http\Model\File;
use Illuminate\Support\Facades\Validator;

class SettingController extends Controller
{


    /*post ajax request*/
    public function index(Request $request)
    {
        $requestData = $request->except('_token');
        $action = $requestData['action'];
        $actionAllow = ['avatarSetting', 'basicSetting'];

        if (in_array($action, $actionAllow)) {

            return $this->$action($requestData);
        } else {


            return json_encode(['state' => 1, 'msg' => '非法操作']);

        }


    }

    private function basicSetting($data)
    {
        $state = 0;
        $msg = '修改成功';
        $user = session('user');


        $rules = [
            // 'username' => 'bail|required|max:15|unique:user',
            //'email' => 'bail|email|max:80|unique:user',
            'introduce' => 'bail|between:6,50',
            'allowMessage' => 'bail|required|max:2',
            'gender' => 'bail|required|max:2'
        ];

        if (trim($data['username']) != $user->username) {

            $rules['username'] = 'bail|required|max:15|unique:user';
        }

        if (trim($data['email']) != $user->email) {

            $rules['email'] = 'bail|email|max:80|unique:user';
        }


        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {

            $state = 1;
            $msg = '请检查你的输入是否合法';

        } else {


            $user['username'] = $data['username'];
            $user['introduce'] = $data['introduce'];
            $user['gender'] = $data['gender'];
            $user['email'] = $data['email'];
            $user['allow_message'] = $data['allowMessage'];

            if ($user->save()) {

                session(['user' => $user]);

            } else {

                $state = 1;
                $msg = '操作失败了,请稍候再试';
            }

        }

        return json_encode(['state' => $state, 'msg' => $msg]);


    }

    private function avatarSetting($data)
    {
        $state = 0;
        $msg = '修改成功';
        $newKey = $this->avatarUpload($data['inputName']);

        if ($newKey) {

            $user = session('user');
            $user->avatar_key = $newKey;

            if (!$user->save()) {
                $state = 1;
                $msg = '头像修改失败';
            }

        } else {

            $state = 1;
            $msg = '文件上传失败';
        }

        return json_encode(['state' => $state, 'msg' => $msg, 'url' => env('imgUrl') . '/' . $newKey]);

    }


    /**
     * avatarUpload
     * @param string $inputName 文件的inputName
     * @return mixed 成功返回创建的对象的key值 不成功false;
     **/
    private function avatarUpload($inputName)
    {
        // $diskInstance=Storage::disk('avatar');

        //$redis->set("list", "a", "ex", "10");

        //AliYunOss::(env(''))

        $aliYunOss = AliYunOss::getOssClient();

        $uploadPath = 'avatar/';

        $fileUpload = new FileUpload($inputName, ['storeDriver' => 'aliYunOss', 'uploadPath' => $uploadPath, 'bucket' => env('bucket')], $aliYunOss);
        $fileUpload->initialize();

        if ($fileUpload->initialize()) {

            if ($fileUpload->execute()) {

                $newKey = $uploadPath . $fileUpload->getVariable('newFileName');
                return $newKey;
            }

        }
        return false;
    }
}
