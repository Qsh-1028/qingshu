<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/6/5 0005
 * Time: 9:56
 */

namespace App\Http\Service;


use App\Models\CompareFace;
use App\Models\User;

class CompareFaceService
{
    private $builder;

    /**
     * 检测图片中是否有头像
     *
     * @author yezi
     *
     * @param $rectData
     * @return bool
     */
    public function checkEmptyRect($rectData)
    {
        $emptyRect = true;
        foreach ($rectData as $rect){
            if($rect != 0){
                $emptyRect = false;
            }
        }

        return $emptyRect;
    }

    /**
     * 记录比对数据
     *
     * @author yezi
     *
     * @param $userId
     * @param $image1
     * @param $image2
     * @param $status
     * @param $compareResult
     * @return mixed
     */
    public function create($userId,$image1,$image2,$status,$score,$compareResult)
    {
        $result = CompareFace::create([
            CompareFace::FIELD_ID_USER        => $userId,
            CompareFace::FIELD_ATTACHMENTS    => ['rect_a'=>$image1,'rect_b'=>$image2],
            CompareFace::FIELD_CONFIDENCE     => $score,
            CompareFace::FIELD_STATUS         => $status,
            CompareFace::FIELD_COMPARE_RESULT => json_encode($compareResult)
        ]);

        return $result;
    }

    /**
     * 检测报告
     *
     * @author yezi
     *
     * @param $compareResult
     * @return array
     */
    public function report($score)
    {
        $level = 1;
        $cpResult = $score;
        if($cpResult >= 0 && $cpResult < 3){
            $keyWorld = '半毛钱脸';
            $level    = 0;
            $message  = '很严肃的告诉你，你们血缘上没有半毛钱关系！';
        }elseif($cpResult >= 3 && $cpResult < 10){
            $keyWorld = '路人脸';
            $level    = 1;
            $message  = '很愉快的告诉你，你们绝对不会是同父异母的兄弟姐妹！';
        }elseif($cpResult >= 10 && $cpResult < 20){
            $keyWorld = '情侣脸';
            $level    = 2;
            $message  = '你们的情侣脸指数跟（赵又廷、高圆圆）（黄晓明、杨颖）差不多，是标准的情侣脸。';
        }elseif ($cpResult >= 20 && $cpResult < 30){
            $keyWorld = '七年情侣脸';
            $level    = 3;
            $message  = '你们在一起的时间越长，就会越像对方，就像邓超和孙俪那样。';
        }elseif ($cpResult >= 30 && $cpResult < 46){
            $keyWorld = '夫妻脸';
            $level    = 4;
            $message  = '你们上辈子肯定是夫妻关系，国民夫妻相。';
        }elseif ($cpResult >= 46 && $cpResult < 70){
            $keyWorld = '兄弟姐妹脸';
            $level    = 5;
            $message  = '你们不是兄弟姐妹吗？';
        }elseif ($cpResult >= 70 && $cpResult < 80){
            $keyWorld = '镜子脸';
            $level    = 6;
            $message  = '自己的照片吧，简直一模一样。';
        }elseif ($cpResult >= 80 && $cpResult <= 100){
            $keyWorld = '自己脸';
            $level    = 7;
            $message  = '别闹了，难道你喜欢你自己？';
        }else{
            $keyWorld = '外星脸'; //系统检测，你不是地球人
            $level    = 8;
            $message  = '系统检测，你（系）们（统）不（出）是（bug）地(了)球人';
        }

        return [
            'key_world'  => $keyWorld,
            'level'      => $level,
            'message'    => $message,
            'confidence' => round($cpResult,1)
        ];
    }

    public function queryBuilder($appID,$username)
    {
        $this->builder = CompareFace::query()
            ->with(['poster'=>function($query){
                $query->select([
                    User::FIELD_ID,
                    User::FIELD_ID_APP,
                    User::FIELD_NICKNAME,
                    User::FIELD_AVATAR,
                    User::FIELD_CREATED_AT
                ]);
            }])
            ->whereHas(CompareFace::REL_POSTER,function ($query)use($appID,$username){
                $query->where(User::FIELD_ID_APP,$appID);
                if($username){
                    $query->where(User::FIELD_NICKNAME,'like','%'.$username.'%');
                }
            });
        return $this;
    }

    /**
     * 排序
     *
     * @author yezi
     *
     * @param $orderBy
     * @param $sortBy
     *
     * @return $this
     */
    public function sort($orderBy, $sortBy)
    {
        $this->builder->orderBy($orderBy, $sortBy);

        return $this;
    }

    public function done()
    {
        return $this->builder;
    }

}