<?php

namespace Edu\Cnm\CreepyOctoMeow;

require_once ("autoload.php");
require_once (dirname(__DIR__) . "/vendor/autoload.php");

use Ramsey\Uuid\Uuid;

/**
 * Profile Class
 *
 * This represents all data contained in a user's Profile.
 *
 * @author Rochelle Lewis <rlewis37@cnm.edu>
 * @version 1.0
 **/

class Profile implements \JsonSerializable {

	use ValidateUuid;

	/**
	 * id for the Profile; this is the Primary Key.
	 * @var Uuid $profileId
	 **/
	private $profileId;

	/**
	 * Profile activation token.
	 * @var string $profileActivationToken
	 **/
	private $profileActivationToken;

	/**
	 * Email address for the Profile. This is unique.
	 * @var string $profileEmail
	 **/
	private $profileEmail;

	/**
	 * Hash value for the Profile password.
	 * @var string $profileHash
	 **/
	private $profileHash;

	/**
	 * User name for the Profile. This is unique.
	 * @var string $profileUsername
	 **/
	private $profileUsername;

	/**
	 * Constructor for this Profile
	 *
	 * @param string|Uuid $newProfileId id of this Profile, or null if a new Profile
	 * @param string|null $newProfileActivationToken activation token for this Profile
	 * @param string $newProfileEmail email address for this Profile
	 * @param string $newProfileHash hash value for the Profile password
	 * @param string $newProfileUsername Username for the Profile
	 * @throws \InvalidArgumentException if data types are not valid
	 * @throws \RangeException if data values are out of bounds
	 * @throws \TypeError if data types violate type hints
	 * @throws \Exception if other exceptions occur
	 **/
	public function __construct($newProfileId, ?string $newProfileActivationToken, string $newProfileEmail, string $newProfileHash, string $newProfileUsername) {
		try {
			$this->setProfileId($newProfileId);
			$this->setProfileActivationToken($newProfileActivationToken);
			$this->setProfileEmail($newProfileEmail);
			$this->setProfileHash($newProfileHash);
			$this->setProfileUsername($newProfileUsername);
		} catch(\InvalidArgumentException | \RangeException | \TypeError | \Exception $exception) {
			$exceptionType = get_class($exception);
			throw (new $exceptionType($exception->getMessage(), 0, $exception));
		}
	}

	/**
	 * accessor method for profile id
	 *
	 * @return int|null value of profile id
	 **/
	public function getProfileId() : Uuid {
		return($this->profileId);
	}

	/**
	 * mutator method for profile id
	 *
	 * @param Uuid|string $newProfileId new value of profile id
	 * @throws \RangeException if $newProfileId is not positive
	 * @throws \TypeError if $newProfileId is not an integer
	 **/
	public function setProfileId($newProfileId) : void {
		try {
			$uuid = self::validateUuid($newProfileId);
		} catch(\InvalidArgumentException | \RangeException | \Exception | \TypeError $exception) {
			$exceptionType = get_class($exception);
			throw (new $exceptionType($exception->getMessage(), 0, $exception));
		}

		//convert and store the profile id
		$this->profileId = $uuid;
	}

	/**
	 * accessor method for profile activation token
	 *
	 * @return string value of profile activation token
	 **/
	public function getProfileActivationToken() : ?string {
		return($this->profileActivationToken);
	}

	/**
	 * mutator method for profile activation token
	 *
	 * @param string|null $newProfileActivationToken new value of profile activation token
	 * @throws \InvalidArgumentException if $newProfileActivationToken is invalid, insecure, or not a valid hash value
	 * @throws \RangeException if $newProfileActivationToken is not exactly 32 characters
	 **/
	public function setProfileActivationToken(?string $newProfileActivationToken) : void {
		//base case: set profile activation token to null for new profiles
		if($newProfileActivationToken === null) {
			$this->profileActivationToken = null;
			return;
		}

		//trim, sanitize, filter existing activation tokens
		$newProfileActivationToken = trim($newProfileActivationToken);
		$newProfileActivationToken = strtolower($newProfileActivationToken);
		$newProfileActivationToken = filter_var($newProfileActivationToken, FILTER_SANITIZE_STRING);
		if(empty($newProfileActivationToken) === true) {
			throw (new \InvalidArgumentException("Profile activation token is invalid or insecure."));
		}

		//verify activation token is valid hash
		if(ctype_xdigit($newProfileActivationToken) === false) {
			throw (new \InvalidArgumentException("Profile activation token is not a valid hash value."));
		}

		//check length
		if(strlen($newProfileActivationToken) !== 32) {
			throw (new \RangeException("Profile activation token is an invalid length."));
		}

		//store activation token
		$this->profileActivationToken = $newProfileActivationToken;
	}

	/**
	 * accessor method for profile email
	 *
	 * @return string value of profile email
	 **/
	public function getProfileEmail() : string {
		return($this->profileEmail);
	}

	/**
	 * mutator method for profile email
	 *
	 * @param string $newProfileEmail new value of profile email
	 * @throws \InvalidArgumentException if $newProfileEmail is invalid or insecure
	 * @throws \RangeException if $newProfileEmail is > 64 characters
	 * @throws \TypeError if $newProfileEmail is not a string
	 **/
	public function setProfileEmail(string $newProfileEmail) : void {
		//sanitize profile email content, check if secure
		$newProfileEmail= trim($newProfileEmail);
		$newProfileEmail = filter_var($newProfileEmail, FILTER_SANITIZE_EMAIL);
		if(empty($newProfileEmail) === true) {
			throw (new \InvalidArgumentException("Profile email is invalid or insecure"));
		}

		//check profile email length
		if(strlen($newProfileEmail) > 128) {
			throw (new \RangeException("Profile email is too long."));
		}

		//store profile email
		$this->profileEmail = $newProfileEmail;
	}

	/**
	 * accessor method for profile password hash
	 *
	 * @return string value of profile password hash
	 **/
	public function getProfileHash() : string {
		return($this->profileHash);
	}

	/**
	 * mutator method for profile password hash
	 *
	 * @param string $newProfileHash new value of profile password hash
	 * @throws \InvalidArgumentException if $newProfileHash is empty, insecure, or not a valid argon hash
	 * @throws \RangeException if $newProfileHash is not 97 characters
	 * @throws \TypeError if $newProfileHash is not a string
	 **/
	public function setProfileHash(string $newProfileHash) : void {
		//trim, filter pass hash input
		$newProfileHash = trim($newProfileHash);
		$newProfileHash = filter_var($newProfileHash, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
		if(empty($newProfileHash) === true) {
			throw (new \InvalidArgumentException("Profile password hash empty or insecure."));
		}

		//check if valid Argon hash value
		$profileHashInfo = password_get_info($newProfileHash);
		if($profileHashInfo["algoName"] !== "argon2i") {
			throw (new \InvalidArgumentException("Profile password hash is invalid."));
		}

		//check if valid length
		if(strlen($newProfileHash) !== 97) {
			throw (new \RangeException("Profile password hash invalid length."));
		}
		/*
		 * New valid length check
		 * if(strlen($newProfileHash) > 97 || strlen($newProfileHash) < 89 ) {
   throw(new \RangeException("user hash is out of range"'));
		*/

		//store password hash value
		$this->profileHash = $newProfileHash;
	}

	/**
	 * accessor method for profile username
	 *
	 * @return string value of profile username
	 **/
	public function getProfileUsername() : string {
		return($this->profileUsername);
	}

	/**
	 * mutator method for profile password username
	 *
	 * @param string $newProfileUsername new value of profile username
	 * @throws \InvalidArgumentException if $newProfileUsername is empty or insecure
	 * @throws \RangeException if $newProfileUsername is > 64 characters
	 * @throws \TypeError if $newProfileUsername is not a string
	 **/
	public function setProfileUsername(string $newProfileUsername) : void {
		$newProfileUsername = trim($newProfileUsername);
		$newProfileUsername = filter_var($newProfileUsername, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
		if(empty($newProfileUsername) === true) {
			throw (new \InvalidArgumentException("Profile username is invalid or insecure"));
		}

		//check for valid length
		if(strlen($newProfileUsername) > 64) {
			throw (new \RangeException("Profile username is too long."));
		}

		//store profile username
		$this->profileUsername = $newProfileUsername;
	}

	/**
	 * inserts this Profile into mySQL
	 *
	 * @param \PDO $pdo PDO connection object
	 * @throws \PDOException when mySQL related errors occur
	 * @throws \TypeError if $pdo is not a PDO connection object
	 **/
	public function insert(\PDO $pdo) : void {

		//create query template
		$query = "INSERT INTO profile(profileId, profileActivationToken, profileEmail, profileHash, profileUsername) VALUES(:profileId, :profileActivationToken, :profileEmail, :profileHash, :profileUsername)";
		$statement = $pdo->prepare($query);

		//bind the member variables to the placeholders in the query template
		$parameters = [
			"profileId" => $this->profileId->getBytes(),
			"profileActivationToken" => $this->profileActivationToken,
			"profileEmail" => $this->profileEmail,
			"profileHash" => $this->profileHash,
			"profileUsername" => $this->profileUsername
		];
		$statement->execute($parameters);
	}

	/**
	 * updates this Profile in mySQL
	 *
	 * @param \PDO $pdo PDO connection object
	 * @throws \PDOException when mySQL related errors occur
	 * @throws \TypeError if $pdo is not a PDO connection object
	 **/
	public function update(\PDO $pdo) : void {

		//create query template
		$query = "UPDATE profile SET profileActivationToken = :profileActivationToken, profileEmail = :profileEmail, profileHash = :profileHash, profileUsername = :profileUsername WHERE profileId = :profileId";
		$statement = $pdo->prepare($query);

		//bind member variables to the placeholders in the template
		$parameters = [
			"profileActivationToken" =>$this->profileActivationToken,
			"profileEmail" => $this->profileEmail,
			"profileHash" => $this->profileHash,
			"profileUsername" => $this->profileUsername,
			"profileId" => $this->profileId->getBytes()
		];
		$statement->execute($parameters);
	}

	/**
	 * deletes this Profile from mySQL
	 *
	 * @param \PDO $pdo PDO connection object
	 * @throws \PDOException when mySQL related errors occur
	 * @throws \TypeError if $pdo is not a PDO connection object
	 **/
	public function delete(\PDO $pdo) : void {

		//create query template
		$query = "DELETE FROM profile WHERE profileId = :profileId";
		$statement = $pdo->prepare($query);

		//bind member variables to the placeholders in the query template
		$parameters = ["profileId" => $this->profileId->getBytes()];
		$statement->execute($parameters);
	}

	/**
	 * gets a Profile by profileId
	 *
	 * @param \PDO $pdo PDO connection object
	 * @param Uuid|string $profileId profile id to search for
	 * @return Profile|null Profile found or null if not found
	 * @throws \PDOException when mySQL related errors occur
	 * @throws \TypeError when variables are not the correct data type
	 **/
	public static function getProfileByProfileId(\PDO $pdo, $profileId) : ?Profile {

		//sanitize profileId before searching
		try {
			$profileId = self::validateUuid($profileId);
		} catch(\InvalidArgumentException | \RangeException | \Exception | \TypeError $exception) {
			throw (new \PDOException($exception->getMessage(), 0, $exception));
		}

		//create query template
		$query = "SELECT profileId, profileActivationToken, profileEmail, profileHash, profileUsername FROM profile WHERE profileId = :profileId";
		$statement = $pdo->prepare($query);

		//bind profile id to placeholder in query template
		$parameters = ["profileId" => $profileId->getBytes()];
		$statement->execute($parameters);

		//grab profile from mysql
		try {
			$profile = null;
			$statement->setFetchMode(\PDO::FETCH_ASSOC);
			$row = $statement->fetch();
			if($row !== false) {
				$profile = new Profile($row["profileId"], $row["profileActivationToken"], $row["profileEmail"], $row["profileHash"], $row["profileUsername"]);
			}
		} catch(\Exception $exception) {
			//if row can't be converted, rethrow it
			throw (new \PDOException($exception->getMessage(), 0, $exception));
		}
		return($profile);
	}

	/**
	 * gets a Profile by activation token
	 *
	 * @param \PDO $pdo PDO connection object
	 * @param string $profileActivationToken activation token to search for
	 * @return Profile|null Profile found or null if not found
	 * @throws \PDOException when mySQL related errors occur
	 * @throws \TypeError when variables are not the correct data type
	 **/
	public static function getProfileByProfileActivationToken(\PDO $pdo, string $profileActivationToken) :?Profile {
		//sanitize, check for valid activation token
		$profileActivationToken = trim($profileActivationToken);
		$profileActivationToken = strtolower($profileActivationToken);
		$profileActivationToken = filter_var($profileActivationToken, FILTER_SANITIZE_STRING);
		if(empty($profileActivationToken) === true) {
			throw (new \PDOException("Profile activation token is invalid or insecure."));
		}

		//create query template
		$query = "SELECT profileId, profileActivationToken, profileEmail, profileHash, profileUsername FROM profile WHERE profileActivationToken = :profileActivationToken";
		$statement = $pdo->prepare($query);

		//bind profile id to placeholder in query template
		$parameters = ["profileActivationToken" => $profileActivationToken];
		$statement->execute($parameters);

		//grab profile from mysql
		try {
			$profile = null;
			$statement->setFetchMode(\PDO::FETCH_ASSOC);
			$row = $statement->fetch();
			if($row !== false) {
				$profile = new Profile($row["profileId"], $row["profileActivationToken"], $row["profileEmail"], $row["profileHash"], $row["profileUsername"]);
			}
		} catch(\Exception $exception) {
			//if row can't be converted, rethrow it
			throw (new \PDOException($exception->getMessage(), 0, $exception));
		}
		return($profile);
	}

	/**
	 * gets a Profile by profileEmail
	 *
	 * @param \PDO $pdo PDO connection object
	 * @param string $profileEmail profile email to search for
	 * @return Profile|null Profile found or null if not found
	 * @throws \PDOException when mySQL related errors occur
	 * @throws \TypeError when variables are not the correct data type
	 **/
	public static function getProfileByProfileEmail(\PDO $pdo, string $profileEmail) :?Profile {
		//sanitize, check for valid profile email
		$profileEmail = trim($profileEmail);
		$profileEmail = filter_var($profileEmail, FILTER_SANITIZE_EMAIL);
		if(empty($profileEmail) === true) {
			throw (new \PDOException("Profile email is invalid or insecure."));
		}

		//create query template
		$query = "SELECT profileId, profileActivationToken, profileEmail, profileHash, profileUsername FROM profile WHERE profileEmail = :profileEmail";
		$statement = $pdo->prepare($query);

		//bind profile id to placeholder in query template
		$parameters = ["profileEmail" => $profileEmail];
		$statement->execute($parameters);

		//grab profile from mysql
		try {
			$profile = null;
			$statement->setFetchMode(\PDO::FETCH_ASSOC);
			$row = $statement->fetch();
			if($row !== false) {
				$profile = new Profile($row["profileId"], $row["profileActivationToken"], $row["profileEmail"], $row["profileHash"], $row["profileUsername"]);
			}
		} catch(\Exception $exception) {
			//if row can't be converted, rethrow it
			throw (new \PDOException($exception->getMessage(), 0, $exception));
		}
		return($profile);
	}

	/**
	 * gets the Profile by profileUsername
	 *
	 * @param \PDO $pdo PDO connection object
	 * @param string $profileUsername profile username to search for
	 * @return Profile|null Profile found or null if not found
	 * @throws \PDOException when mySQL related errors occur
	 * @throws \TypeError when variables are not the correct data type
	 **/
	public static function getProfileByProfileUsername(\PDO $pdo, string $profileUsername) : ?Profile {
		//sanitize, check for valid profile username
		$profileUsername = trim($profileUsername);
		$profileUsername = filter_var($profileUsername, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
		if(empty($profileUsername) === true) {
			throw (new \PDOException("Profile username is invalid or insecure."));
		}

		//create query template
		$query = "SELECT profileId, profileActivationToken, profileEmail, profileHash, profileUsername FROM profile WHERE profileUsername = :profileUsername";
		$statement = $pdo->prepare($query);

		//bind profile id to placeholder in query template
		$parameters = ["profileUsername" => $profileUsername];
		$statement->execute($parameters);

		//grab profile from mysql
		try {
			$profile = null;
			$statement->setFetchMode(\PDO::FETCH_ASSOC);
			$row = $statement->fetch();
			if($row !== false) {
				$profile = new Profile($row["profileId"], $row["profileActivationToken"], $row["profileEmail"], $row["profileHash"], $row["profileUsername"]);
			}
		} catch(\Exception $exception) {
			//if row can't be converted, rethrow it
			throw (new \PDOException($exception->getMessage(), 0, $exception));
		}
		return($profile);
	}

	/**
	 * gets all Profiles
	 *
	 * @param \PDO $pdo PDO connection object
	 * @return \SplFixedArray SplFixedArray of Profiles found
	 * @throws \PDOException when mySQL related errors occur
	 * @throws \TypeError when variables are not the correct data type
	 **/
	public static function getAllProfiles(\PDO $pdo) : \SplFixedArray {
		//create query template
		$query = "SELECT profileId, profileActivationToken, profileEmail, profileHash, profileUsername FROM profile";
		$statement = $pdo->prepare($query);
		$statement->execute();

		//build an array of profiles
		$profiles = new \SplFixedArray($statement->rowCount());
		$statement->setFetchMode(\PDO::FETCH_ASSOC);
		while(($row = $statement->fetch()) !== false) {
			try {
				$profile = new Profile($row["profileId"], $row["profileActivationToken"], $row["profileEmail"], $row["profileHash"], $row["profileUsername"]);
				$profiles[$profiles->key()] = $profile;
				$profiles->next();
			} catch(\Exception $exception) {
				// if the row couldn't be converted, rethrow it
				throw(new \PDOException($exception->getMessage(), 0, $exception));
			}
		}
		return ($profiles);
	}

	/**
	 * formats the state variables for JSON serialization
	 *
	 * @return array resulting state variables to serialize
	 **/
	public function jsonSerialize() {
		$fields = get_object_vars($this);
		$fields["profileId"] = $this->profileId->toString();
		return($fields);
	}
}