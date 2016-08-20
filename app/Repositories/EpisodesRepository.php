<?php

namespace App\Repositories;

use App\Filter\Filter;
use App\Models\Episode;
use App\Models\Feed;
use Illuminate\Database\Eloquent\Builder;

class EpisodesRepository
{
    public static function feedId($episodeId)
    {
        return Episode::whereId($episodeId)->first()->feed_id;
    }

    public function retriveByFeedId($feedId, Filter $filter)
    {
        $episodes = Episode::take($filter->limit)
                ->skip($filter->offset)
                ->orderBy('id', $filter->order)
                ->whereFeedId($feedId)
                ->where('title', 'LIKE', "%$filter->term%")
                ->get();

        if ($episodes->isEmpty()) {
            return false;
        }

        return $episodes;
    }

    /**
     * @param integer $feedId id of feed
     */
    public function getTotalEpisodes($feedId)
    {
        return Episode::whereFeedId($feedId)->count();
    }

    public function latests(Filter $filter)
    {
        return Episode::take($filter->limit)
            ->join('feeds', 'episodes.feed_id', '=', 'feeds.id')
            ->skip($filter->offset)
            ->orderBy('published_date', $filter->order)
            ->select(
                'episodes.*',
                'feeds.name',
                'feeds.thumbnail_30',
                'feeds.thumbnail_60',
                'feeds.thumbnail_100',
                'feeds.thumbnail_600'
            )
            ->get();
    }
}