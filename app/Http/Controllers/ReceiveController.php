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

        $result = $photos->count() ? ['result' => 'success', 'photos' => $photos] : ['result' => 'error', 'photos' => null];
        return response()->json($result);
    }
}
