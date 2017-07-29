<?php
namespace Tests\Integration\Feeds;

use App\Models\Episode;
use App\Models\Feed;
use App\Transform\FeedTransformer;
use Carbon\Carbon;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class RetrieveFeedsTest extends TestCase
{
    use DatabaseMigrations;
    
    /** @test */
    public function unauthenticated_client_cannot_retrieve_feed_by_name()
    {
        $feedName = 'devnaestrada';
        $feed = factory(Feed::class)->create([
            'name' => $feedName,
            'slug' => $feedName,
        ]);
    
        factory(Episode::class)->times(6)->create([
            'feed_id' => $feed->id
        ]);
    
        $this->get('/v1/feeds/name/' . $feedName)
            ->seeStatusCode(401);
    }
    
    /** @test */
    public function unauthenticated_client_cannot_retrieve_feed_by_id()
    {
        $feed = factory(Feed::class)->create();
        
        factory(Episode::class, 3)->create([
            'feed_id' => $feed->id
        ]);
    
        $this->get('/v1/feeds/' . $feed->id)
            ->seeStatusCode(401);
    }
    
    /** @test */
    public function unauthenticated_client_cannot_retrieve_latests_feeds()
    {
        factory(Episode::class, 3)->create();
        
        $this->get('/v1/feeds/latest')
            ->seeStatusCode(401);
    }
    
    /** @test */
    public function it_returns_feed_by_name()
    {
        $this->authenticate();
        
        $feed = factory(Feed::class)->create([
            'name' => 'devnaestrada',
            'slug' => 'devnaestrada',
        ]);
    
        factory(Episode::class, 6)->create([
            'feed_id' => $feed->id
        ]);
    
        $response = $this->get('/v1/feeds/name/devnaestrada')
            ->seeStatusCode(200)
            ->seeJsonStructure([
                'data' => [
                    $this->getDefaultFeedStructure()
                ]
            ]);
        $response = collect(json_decode($response->response->getContent())->data);
        $this->assertCount(1, $response);
    }
    
    /** @test */
    public function it_returns_404_when_retrieving_non_existent_feed_by_name()
    {
        $this->authenticate();
        
        $feed = factory(Feed::class)->create([
            'name' => 'somefeed',
            'slug' => 'somefeed',
        ]);
        
        factory(Episode::class, 6)->create([
            'feed_id' => $feed->id
        ]);
        
        $this->get('/v1/feeds/name/anotherfeed')
            ->seeStatusCode(404);
    }
    
    /** @test */
    public function it_returns_feed_by_id()
    {
        $this->authenticate();
    
        $feed = factory(Feed::class)->create();
    
        factory(Episode::class, 3)->create([
            'feed_id' => $feed->id
        ]);
    
        $this->get('/v1/feeds/1')
            ->seeStatusCode(200)
            ->seeJsonStructure([
                'data' => [
                    $this->getDefaultFeedStructure()
                ]
            ]);
    }
    
    /** @test */
    public function it_returns_404_when_retrieving_non_existent_feed_by_id()
    {
        $this->authenticate();
        
        $this->get('/v1/feeds/1')
            ->seeStatusCode(404);
    }
    
    /** @test */
    public function it_returns_latests_feeds()
    {
        $this->authenticate();
    
        factory(Episode::class, 3)->create();
        
        $response = $this->get('/v1/feeds/latest')
                        ->seeStatusCode(200)
                        ->seeJsonStructure($this->getDefaultStructure());
    
        $response = collect(json_decode($response->response->getContent())->data);
        $this->assertCount(3, $response);
    }
    
    /** @test */
    public function it_returns_404_when_retrieving_latests_feeds_having_no_feeds()
    {
        $this->authenticate();
        
        $this->get('/v1/feeds/latest')
            ->seeStatusCode(404);
    }
    
    /** @test */
    public function it_retrieves_top_feeds()
    {
        $this->authenticate();
    
        $firstFeed = factory(Feed::class)->create([
            'listeners' => 5,
            'last_episode_at' => (string) Carbon::now(),
        ]);
        $secondFeed = factory(Feed::class)->create([
            'listeners' => 3,
            'last_episode_at' => (string) Carbon::now()->subDay(2),
        ]);
        $thirdFeed = factory(Feed::class)->create([
            'listeners' => 1,
            'last_episode_at' => (string) Carbon::now()->subDay(4),
        ]);
        
        $response = $this->get('/v1/feeds/top')
            ->seeStatusCode(200)
            ->seeJson([
               'data' => [
                   (new FeedTransformer)->transform($firstFeed->toArray()),
                   (new FeedTransformer)->transform($secondFeed->toArray()),
                   (new FeedTransformer)->transform($thirdFeed->toArray()),
               ]
            ])
            ->seeJsonStructure($this->getDefaultStructure());
    
        /*$response = collect(json_decode($response->response->getContent())->data);
        $this->assertCount(3, $response);*/
    }
    
    /** @test */
    public function it_returns_404_when_retrieving_top_feeds_having_no_feeds()
    {
        $this->authenticate();
        
        $this->get('/v1/feeds/top')
            ->seeStatusCode(404);
    }

    private function getDefaultStructure()
    {
        return [
            'data' => [
                '*' => $this->getDefaultFeedStructure()
            ]
        ];
    }

    private function getDefaultFeedStructure()
    {
        return [
            'id',
            'name',
            'url',
            'thumbnail_30',
            'thumbnail_60',
            'thumbnail_100',
            'thumbnail_600',
            'total_episodes',
            'listeners',
            'last_episode_at'
        ];
    }

}