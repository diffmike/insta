<?php

namespace App\Http\Controllers;

use App\Photo;
use App\Http\Requests;
use Vinkla\Instagram\InstagramManager;

/**
 * Class StoreController
 * @package App\Http\Controllers
 */
class StoreController extends Controller
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
     * Store account(s) photos
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store()
    {
        foreach (explode(',', request()->get('account')) as $account) {
            $id = $this->getId($account);
            $this->storeUserMedia($id);
        }
        return response()->json(['result' => 'success']);
    }

    /**
     * Store all account photos by ID
     *
     * @param int $id
     * @return array|null
     */
    private function storeUserMedia($id)
    {
        if (!$id) return null;
        $page = $this->instagram->getUserMedia($id);
        $result = $this->storeMediaPage($page);
        do {
            $result = $this->storeMediaPage($page, $result);
        } while($page = $this->instagram->pagination($page));

        return $result;
    }

    /**
     * Process and store one page of photos
     *
     * @param  object   $media
     * @param array     $result
     * @return array
     */
    private function storeMediaPage($media, $result = [])
    {
        if (!$media) return [];
        $media = json_decode(json_encode($media), true);
        foreach($media['data'] as $item) {
            if (array_get($item, 'type') != 'image') continue;
            $photo = [
                'url'       => array_get($item, 'images.standard_resolution.url'),
                'likes'     => array_get($item, 'likes.count'),
                'comments'  => array_get($item, 'comments.count'),
                'caption'   => array_get($item, 'caption.text'),
                'user_id'   => array_get($item, 'user.id'),
                'user_name' => array_get($item, 'user.username'),
                'id'        => array_get($item, 'id')
            ];
            Photo::where('url', $photo['url'])->delete();
            Photo::create($photo);
            $result[] = $photo;
        }

        return $result;
    }

    /**
     * Get account ID
     *
     * @param string $account
     * @return int
     */
    private function getId($account)
    {
        $user = json_decode(json_encode($this->instagram->searchUser($account, 1)), true);
        return array_get($user, 'data.0.id', 0);
    }
}
