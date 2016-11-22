<?php

namespace App\Http\Controllers;

use Abraham\TwitterOAuth\TwitterOAuth;
use Symfony\Component\DomCrawler\Crawler;
use App\Tweet;
use App\Post;
use SammyK;

class TwitterController extends Controller
{

	/*
		Постит в twitter-аккаунт https://twitter.com/talam0nal новости с портала foreignpolicy.com
	*/
	public function post() 
	{
		$string = file_get_contents('https://foreignpolicy.com/channel/breaking-news-2');
		$crawler = new Crawler($string);
		$crawler = $crawler->filter('h2.post-title');
		foreach ($crawler as $domElement) {
    		$titles[] = $domElement->nodeValue;
		}

		$l = new Crawler($string);
		$l = $l->filter('h2.post-title > a')->each(function ($node, $i) {
			$links[] = $node->attr('href');
			return $links;
		});

		$posts = [];
		foreach ($titles as $key => $title) {
			$posts[] = [
				'title' => $title,
				'link'  => $l[$key][0],
			];
		}

		foreach ($posts as $item) {
			if ($this->tweetIsExists($item['link'])) {
				continue;
			} else {
				$this->postTweet($item['title'], $item['link']);
				$this->saveTweet($item['link'], $item['title']);
				break;
			}
		}
		
	}

	/*
		Постит в фейсбук https://www.facebook.com/profile.php?id=100014314387223
		твиты с ключевым словом "финансы"
	*/
	public function facebook(SammyK\LaravelFacebookSdk\LaravelFacebookSdk $fb)
	{
		$tweets = $this->searchTweets();
		$statuses = $tweets->statuses;
		foreach ($statuses as $item) {
			if ($this->postIsExists($item->text)) {
				continue;
			} else {
				$this->savePosts($item->text);
				$token = 'EAAEoewikGSIBAAFI2hhAPAk53aoZCMGr2HR3k7YWbWPoWaNCkUt14H7KJTazDow2luD6lIcyMpsQOULKPjQwqYPisGwqWm4X4MdqY9IT9zgxEvKw1ZBayIRrIfYXnAucTRcRga1nZBv5p6CbG5H83krbYUMUbkRJvBd1Py2orruI1O1n2hc';
				$data = [
				    'message' => $item->text,
				];
				$response = $fb->post('feed', $data, $token);
				break;
			}
		}
	}

	/*
		Ищет твиты по ключевому слову "финансы"
	*/
	public function searchTweets()
	{
		$connection = new TwitterOAuth('FmQQGhq0UsGfNRfLVSCOf4f24', 'eznk6J3rFPtJ9pTlDyUGQdBk8x3LrlbuoS81kTCotEpzC93nSv', '57973292-wkt8VgfidccFPMgrZ02BaNgftKlwm8Kztj3OPzSvZ', 'ZDrYi34c7nOb9ctVbGu0IMllJIaN83kAwvsXsvBuHFV8Y');
		return $connection->get("search/tweets", ["q" => "финансы"]);
	}
	
	private function postTweet($title, $url)
	{
		$connection = new TwitterOAuth('FmQQGhq0UsGfNRfLVSCOf4f24', 'eznk6J3rFPtJ9pTlDyUGQdBk8x3LrlbuoS81kTCotEpzC93nSv', '57973292-wkt8VgfidccFPMgrZ02BaNgftKlwm8Kztj3OPzSvZ', 'ZDrYi34c7nOb9ctVbGu0IMllJIaN83kAwvsXsvBuHFV8Y');
		$statues = $connection->post("statuses/update", ["status" => $title.' '.$url]);
	}

	private function saveTweet($url, $title)
	{
		$tweet = new Tweet;
		$tweet->url = $url;
		$tweet->title = $title;
		$tweet->save();
	}

	private function tweetIsExists($url)
	{
		$tweet = Tweet::where('url', $url)->get();
		return count($tweet);
	}

	private function savePosts($post)
	{
		$posts = new Post;
		$posts->title = $post;
		$posts->save();
	}

	private function postIsExists($post)
	{
		$post = Post::where('title', $post)->get();
		return count($post);		
	}

}