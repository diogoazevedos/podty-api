<?php
namespace App\Repositories;

use App\Models\Feed;

class FeedRepository
{
    public function __construct()
    {
        $this->feed = new Feed;
    }

    public function findByName($name)
    {
        return Feed::where('name', 'like', "%$name%")->get();
    }

    public function first($id)
    {
        return $this->findById($id)->first();
    }

    public function findById($id)
    {
        return Feed::whereId($id)->get();
    }

    /**
     * Busca pelos feeds que recentemente publicaram novos episodios
     * @param integer $limit limite de quantidade de retorno, por padrao, 10
     */
    public function latests($limit = 10)
    {
        return Feed::take($limit)
            ->orderBy('last_episode_at', 'DESC')
            ->get();
    }

    public function totalEpisodes($id)
    {
        return Feed::whereId($id)->select('total_episodes')->first()->toArray();
    }

    public function updateOrCreate(array $feed)
    {
        return $this->feed->upsert($feed, ['url' => $feed['url']], true);
    }

    public function updateTotalEpisodes($id)
    {
        Feed::whereId($id)->update([
                'total_episodes' => (new EpisodesRepository)->getTotalEpisodes($id)
            ]);
    }

    public function updateLastEpisodeDate($id, $date)
    {
        Feed::whereId($id)->update([
                'last_episode_at' => $date
            ]);
    }
}