<?php

namespace App\Http\Controllers;

use App\Photo;
use App\Http\Requests;
use Vinkla\Instagram\InstagramManager;

/**
 * Class ReceiveController
 * @package App\Http\Controllers
 */
class ReceiveController extends Controller
{
    /**
     * @var InstagramManager
     */
    protected $instagram;

    /**
     * @param InstagramManager $instagram
     */
    public function __construct(InstagramManager $instagram)
    {
        $this->instagram = $instagram;
    }

    /**
     * Get one photo detailed info
     *
     * @param $id photo id
     * @return \Illuminate\Http\JsonResponse
     */
    public function photo($id)
    {
        $photo = $this->instagram->getMedia($id);

        $result = $photo ? ['result' => 'success', 'photo' => $photo] : ['result' => 'error', 'photo' => null];
        return response()->json($result);
    }

    /**
     * Get stored photos of account
     *
     * @param $account
     * @return \Illuminate\Http\JsonResponse
     */
    public function photos($account)
    {
        $photos = Photo::whereUserName($account);
        if ($count = request()->get('count')) {
            $photos = $photos->limit($count);
        }
        if ($from = request()->get('from')) {
            $photos = $photos->offset($from);
        }
        $photos = $photos->get();
        if (request()->get('split_counters')) {
            $photos = $this->splitCounters($photos);
        }

        $result = $photos->count() ? ['result' => 'success', 'photos' => $photos] : ['result' => 'error', 'photos' => null];
        return response()->json($result);
    }

    /**
     * Split likes and comments counters by day
     *
     * @param $photos
     * @return mixed
     */
    private function splitCounters($photos)
    {
        foreach($photos as $photo) {
            $photo->splited_comments = $this->splitComments($this->instagram->getMediaComments($photo->id));
            $photo->splited_likes = $this->splitLikes($this->instagram->getMedia($photo->id));
        }
        return $photos;
    }

    /**
     * Split likes
     *
     * @param $photo
     * @return mixed
     */
    private function splitLikes($photo)
    {
        $photo = json_decode(json_encode($photo), true);
        $created = (int)array_get($photo, 'data.created_time');
        $likesCount = (int)array_get($photo, 'data.likes.count');
        $result = [];
        $diff = ceil((time() - $created) / 3600 / 24);
        for($i = $created; $i <= time(); $i += 24 * 60 * 60) {
            $fivePercent = $likesCount * 0.05;
            $result[date('d-m-Y', $i)] = $diff == 1 ? $likesCount : (floor($likesCount / $diff) + rand($fivePercent * -1, $fivePercent));
            $likesCount -= $result[date('d-m-Y', $i)];
            $diff--;
        }
        return $result;
    }

    /**
     * Split comments
     *
     * @param $comments
     * @return array
     */
    private function splitComments($comments)
    {
        $comments = json_decode(json_encode($comments), true);
        $result = [];
        foreach(array_get($comments, 'data', []) as $comment) {
            $date = date('d-m-Y', array_get($comment, 'created_time', 0));
            $result[$date] = isset($result[$date]) ? ++$result[$date] : 1;
        }

        return $result;
    }
}
