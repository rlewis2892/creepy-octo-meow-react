<?php

namespace Edu\Cnm\CreepyOctoMeow\Test;

use Edu\Cnm\CreepyOctoMeow\{Profile, Post};

//grab the project test parameters
require_once ("CreepyOctoMeowTest.php");

//grab the classes under scrutiny
require_once (dirname(__DIR__) . "/autoload.php");

//grab the uuid generator
require_once (dirname(__DIR__, 2) . "/lib/uuid.php");

/**
 * Full PHPUnit test for the Post class
 *
 * This is a complete PHPUnit test of the Post class. It is complete because *ALL* mySQL/PDO enabled methods
 * are tested for both invalid and valid inputs.
 *
 * @see Post
 * @author Rochelle Lewis <rlewis37@cnm.edu>
 **/
class PostTest extends CreepyOctoMeowTest {
	/**
	 * content of the Post
	 * @var string $VALID_CONTENT
	 **/
	protected $VALID_CONTENT = "This is a valid post!";

	/**
	 * content of the Post to test update method
	 * @var string $VALID_CONTENT_2
	 **/
	protected $VALID_CONTENT_2 = "This is an updated post! Yay!";

	/**
	 * date of the Post
	 * @var \DateTime $VALID_DATE
	 **/
	protected $VALID_DATE = null;

	/**
	 * beginning date to test post date range search
	 * @var \DateTime $SUNRISE_DATE
	 **/
	protected $SUNRISE_DATE = null;

	/**
	 * end date to test post date range search
	 * @var \DateTime $SUNSET_DATE
	 **/
	protected $SUNSET_DATE = null;

	/**
	 * title of the Post
	 * @var string $VALID_TITLE
	 **/
	protected $VALID_TITLE = "I'm a valid post title!";

	/**
	 * Profile that created the Post; this is to test foreign key relations
	 * @var Profile profile
	 **/
	protected $profile = null;

	/**
	 * create dependent objects before running each test
	 **/
	public final function setUp() : void {
		//run the default setUp() method first
		parent::setUp();

		//create and insert a profile to be the author of the test post
		$activation = bin2hex(random_bytes(16));
		$hash = password_hash("abc123", PASSWORD_ARGON2I, ["time_cost" => 9]);
		$profileId = generateUuidV4();

		$this->profile = new Profile($profileId, $activation, "drumpf@tinyhands.ru", $hash,  "bernie");
		$this->profile->insert($this->getPDO());

		//create a valid date for the Post
		$this->VALID_DATE = new \DateTime();

		//create a valid SUNRISE date for date range check
		$this->SUNRISE_DATE = new \DateTime();
		$this->SUNRISE_DATE->sub(new \DateInterval("P10D"));

		//create a valid SUNSET date for date range check
		$this->SUNSET_DATE = new \DateTime();
		$this->SUNSET_DATE->add(new \DateInterval("P10D"));
	}

	/**
	 * test inserting a valid Post and verify that the actual mySQL data matches
	 **/
	public function testInsertValidPost() {
		//count the number of rows and save it for later
		$numRows = $this->getConnection()->getRowCount("post");

		//create a new post and insert
		$postId = generateUuidV4();
		$post = new Post($postId, $this->profile->getProfileId(), $this->VALID_CONTENT, $this->VALID_DATE, $this->VALID_TITLE);
		$post->insert($this->getPDO());

		//grab the post back from mysql and check if all fields match
		$pdoPost = Post::getPostByPostId($this->getPDO(), $post->getPostId());
		$this->assertEquals($numRows + 1, $this->getConnection()->getRowCount("post"));
		$this->assertEquals($pdoPost->getPostProfileId(), $this->profile->getProfileId());
		$this->assertEquals($pdoPost->getPostContent(), $this->VALID_CONTENT);
		$this->assertEquals($pdoPost->getPostTitle(), $this->VALID_TITLE);
		//format the date to seconds since the beginning of time to avoid round off error
		$this->assertEquals($pdoPost->getPostDate()->getTimestamp(), $this->VALID_DATE->getTimestamp());
	}

	/**
	 * test inserting a Post, editing it, and then updating it
	 **/
	public function testUpdateValidPost() {
		//count the number of rows and save it for later
		$numRows = $this->getConnection()->getRowCount("post");

		//create a new post and insert
		$postId = generateUuidV4();
		$post = new Post($postId, $this->profile->getProfileId(), $this->VALID_CONTENT, $this->VALID_DATE, $this->VALID_TITLE);
		$post->insert($this->getPDO());

		//edit the post and run update method
		$post->setPostContent($this->VALID_CONTENT_2);
		$post->update($this->getPDO());

		//grab the post back from mysql and check if all fields match
		$pdoPost = Post::getPostByPostId($this->getPDO(), $post->getPostId());
		$this->assertEquals($numRows + 1, $this->getConnection()->getRowCount("post"));
		$this->assertEquals($pdoPost->getPostProfileId(), $this->profile->getProfileId());
		$this->assertEquals($pdoPost->getPostContent(), $this->VALID_CONTENT_2);
		$this->assertEquals($pdoPost->getPostTitle(), $this->VALID_TITLE);
		//format the date to seconds since the beginning of time to avoid round off error
		$this->assertEquals($pdoPost->getPostDate()->getTimestamp(), $this->VALID_DATE->getTimestamp());
	}

	/**
	 * test creating a Post and then deleting it
	 **/
	public function testDeleteValidPost() {
		//count the number of rows and save it for later
		$numRows = $this->getConnection()->getRowCount("post");

		//create a new post and insert
		$postId = generateUuidV4();
		$post = new Post($postId, $this->profile->getProfileId(), $this->VALID_CONTENT, $this->VALID_DATE, $this->VALID_TITLE);
		$post->insert($this->getPDO());

		//verify the row has been inserted, then run delete
		$this->assertEquals($numRows + 1, $this->getConnection()->getRowCount("post"));
		$post->delete($this->getPDO());

		//try and grab it back from mysql and verify that you get nothing
		$pdoPost = Post::getPostByPostId($this->getPDO(), $post->getPostId());
		$this->assertNull($pdoPost);
		$this->assertEquals($numRows, $this->getConnection()->getRowCount("post"));
	}

	/**
	 * test grabbing a post that does not exist
	 **/
	public function testGetInvalidPostByPostId() : void {
		$post = Post::getPostByPostId($this->getPDO(), generateUuidV4());
		$this->assertNull($post);
	}

	/**
	 * test grabbing Posts by Profile Id
	 **/
	public function testGetValidPostsByPostProfileId() {
		//count the number of rows and save it for later
		$numRows = $this->getConnection()->getRowCount("post");

		//create a new post and insert
		$postId = generateUuidV4();
		$post = new Post($postId, $this->profile->getProfileId(), $this->VALID_CONTENT, $this->VALID_DATE, $this->VALID_TITLE);
		$post->insert($this->getPDO());

		//grab the posts from mysql, verify row count and namespace is correct
		$results = Post::getPostsByPostProfileId($this->getPDO(), $this->profile->getProfileId());
		$this->assertEquals($numRows + 1, $this->getConnection()->getRowCount("post"));
		$this->assertCount(1, $results);
		$this->assertContainsOnlyInstancesOf("Edu\\Cnm\\CreepyOctoMeow\\Post", $results);

		//verify that all fields match
		$pdoPost = $results[0];
		$this->assertEquals($pdoPost->getPostId(), $post->getPostId());
		$this->assertEquals($pdoPost->getPostContent(), $this->VALID_CONTENT);
		$this->assertEquals($pdoPost->getPostTitle(), $this->VALID_TITLE);
		//format the date to seconds since the beginning of time to avoid round off error
		$this->assertEquals($pdoPost->getPostDate()->getTimestamp(), $this->VALID_DATE->getTimestamp());
	}

	/**
	 * test grabbing Posts by a Profile Id that does not exist
	 **/
	public function testGetPostsByInvalidPostProfileId() {
		$posts = Post::getPostsByPostProfileId($this->getPDO(), generateUuidV4());
		$this->assertCount(0, $posts);
	}

	/**
	 * test grabbing Posts by post content
	 **/
	public function testGetValidPostsByPostContent() {
		//count the number of rows and save it for later
		$numRows = $this->getConnection()->getRowCount("post");

		//create a new post and insert
		$postId = generateUuidV4();
		$post = new Post($postId, $this->profile->getProfileId(), $this->VALID_CONTENT, $this->VALID_DATE, $this->VALID_TITLE);
		$post->insert($this->getPDO());

		//grab the posts from mysql, verify row count and namespace is correct
		$results = Post::getPostsByPostContent($this->getPDO(), $this->VALID_CONTENT);
		$this->assertEquals($numRows + 1, $this->getConnection()->getRowCount("post"));
		$this->assertCount(1, $results);
		$this->assertContainsOnlyInstancesOf("Edu\\Cnm\\CreepyOctoMeow\\Post", $results);

		//verify that all fields match
		$pdoPost = $results[0];
		$this->assertEquals($pdoPost->getPostId(), $post->getPostId());
		$this->assertEquals($pdoPost->getPostProfileId(), $this->profile->getProfileId());
		$this->assertEquals($pdoPost->getPostTitle(), $this->VALID_TITLE);
		//format the date to seconds since the beginning of time to avoid round off error
		$this->assertEquals($pdoPost->getPostDate()->getTimestamp(), $this->VALID_DATE->getTimestamp());
	}

	/**
	 * test grabbing Posts by content that does not exist
	 **/
	public function testGetPostsByInvalidPostContent() {
		$posts = Post::getPostsByPostContent($this->getPDO(), "you will find nothing");
		$this->assertCount(0, $posts);
	}

	/**
	 * test grabbing Posts by post date range
	 **/
	public function testGetValidPostsByPostDateRange() {
		//count the number of rows and save it for later
		$numRows = $this->getConnection()->getRowCount("post");

		//create a new post and insert
		$postId = generateUuidV4();
		$post = new Post($postId, $this->profile->getProfileId(), $this->VALID_CONTENT, $this->VALID_DATE, $this->VALID_TITLE);
		$post->insert($this->getPDO());

		//grab the posts from mysql, verify row count and namespace is correct
		$results = Post::getPostsByPostDateRange($this->getPDO(), $this->SUNRISE_DATE, $this->SUNSET_DATE);
		$this->assertEquals($numRows + 1, $this->getConnection()->getRowCount("post"));
		$this->assertCount(1, $results);
		$this->assertContainsOnlyInstancesOf("Edu\\Cnm\\CreepyOctoMeow\\Post", $results);

		//verify that all fields match
		$pdoPost = $results[0];
		$this->assertEquals($pdoPost->getPostId(), $post->getPostId());
		$this->assertEquals($pdoPost->getPostProfileId(), $this->profile->getProfileId());
		$this->assertEquals($pdoPost->getPostContent(), $this->VALID_CONTENT);
		$this->assertEquals($pdoPost->getPostTitle(), $this->VALID_TITLE);
		//format the date to seconds since the beginning of time to avoid round off error
		$this->assertEquals($pdoPost->getPostDate()->getTimestamp(), $this->VALID_DATE->getTimestamp());
	}

	/**
	 * test grabbing Posts by a date that does not exist
	 **/
	public function testGetPostsByInvalidDateRange() {
		$posts = Post::getPostsByPostDateRange($this->getPDO(), $this->SUNRISE_DATE, $this->SUNSET_DATE);
		$this->assertCount(0, $posts);
	}

	/**
	 * test grabbing Posts by title
	 **/
	public function testGetValidPostsByPostTitle() {
		//count the number of rows and save it for later
		$numRows = $this->getConnection()->getRowCount("post");

		//create a new post and insert
		$postId = generateUuidV4();
		$post = new Post($postId, $this->profile->getProfileId(), $this->VALID_CONTENT, $this->VALID_DATE, $this->VALID_TITLE);
		$post->insert($this->getPDO());

		//grab the posts from mysql, verify row count and namespace is correct
		$results = Post::getPostsByPostTitle($this->getPDO(), $this->VALID_TITLE);
		$this->assertEquals($numRows + 1, $this->getConnection()->getRowCount("post"));
		$this->assertCount(1, $results);
		$this->assertContainsOnlyInstancesOf("Edu\\Cnm\\CreepyOctoMeow\\Post", $results);

		//verify that all fields match
		$pdoPost = $results[0];
		$this->assertEquals($pdoPost->getPostId(), $post->getPostId());
		$this->assertEquals($pdoPost->getPostProfileId(), $this->profile->getProfileId());
		$this->assertEquals($pdoPost->getPostContent(), $this->VALID_CONTENT);
		//format the date to seconds since the beginning of time to avoid round off error
		$this->assertEquals($pdoPost->getPostDate()->getTimestamp(), $this->VALID_DATE->getTimestamp());
	}

	/**
	 * test grabbing Posts by a title that does not exist
	 **/
	public function testGetPostsByInvalidPostTitle() {
		$posts = Post::getPostsByPostTitle($this->getPDO(), "you will find nothing");
		$this->assertCount(0, $posts);
	}

	/**
	 * test grabbing all Posts
	 **/
	public function testGetAllValidPosts() {
		//count the number of rows and save it for later
		$numRows = $this->getConnection()->getRowCount("post");

		//create a new post and insert
		$postId = generateUuidV4();
		$post = new Post($postId, $this->profile->getProfileId(), $this->VALID_CONTENT, $this->VALID_DATE, $this->VALID_TITLE);
		$post->insert($this->getPDO());

		//grab the posts from mysql, verify row count and namespace is correct
		$results = Post::getAllPosts($this->getPDO());
		$this->assertEquals($numRows + 1, $this->getConnection()->getRowCount("post"));
		$this->assertCount(1, $results);
		$this->assertContainsOnlyInstancesOf("Edu\\Cnm\\CreepyOctoMeow\\Post", $results);

		//verify that all fields match
		$pdoPost = $results[0];
		$this->assertEquals($pdoPost->getPostId(), $post->getPostId());
		$this->assertEquals($pdoPost->getPostProfileId(), $this->profile->getProfileId());
		$this->assertEquals($pdoPost->getPostContent(), $this->VALID_CONTENT);
		$this->assertEquals($pdoPost->getPostTitle(), $this->VALID_TITLE);
		//format the date to seconds since the beginning of time to avoid round off error
		$this->assertEquals($pdoPost->getPostDate()->getTimestamp(), $this->VALID_DATE->getTimestamp());
	}
}