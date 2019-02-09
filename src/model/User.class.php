<?php

/**
* User class modelizes a registered user. First class to be designed, this class has several
* characteristics that are implemented as well in other classes. For instance, an instance of the
* class is related to a precise row in a table of the DB ("users" in this case), and all the
* fields/values of that row are stored in the unique field of the class. Such instance can be
* created by giving the user's pseudonym or by using an already existing array (for example, the
* one generated by the Header file). Methods are used to perform small operations on that row,
* while static methods can be used to check the availability of some pseudo or e-mail address,
* along user account creation. However, this class is only instantiated when editing/monitoring
* accounts; checking user information upon logging is done "manually" in the Header file.
*/

class User
{
   private $_data;

   /*
   * Constructor.
   *
   * @param mixed $arg[]  Existing array corresponding to that user or the user's pseudonym
   * @throws Exception    If the user cannot be found or does not exist
   */
   
   public function __construct($arg)
   {
      if(is_array($arg))
      {
         $this->_data = $arg;
      }
      else
      {
         $this->_data = Database::secureRead("SELECT * FROM users WHERE pseudo=?", array($arg), true);
         
         if($this->_data == NULL)
            throw new Exception('User does not exist.');
         else if(sizeof($this->_data) == 3)
            throw new Exception('User could not be found: '. $this->_data[2]);
      }
   }
   
   /*
   * Static method to create a new user account. After calling this method, the account exists, but
   * needs to be confirmed by e-mail. It is worth noting that the policy for storing a password is
   * to hash (with SHA1) the concatenation of the pseudonym, a random secret (stored in the DB;
   * sort of salt) and the plain text password. This should prevent dictionnary attacks.
   *
   * @param string $pseudo    The pseudonym of the new user
   * @param string $email     The e-mail address of the new user
   * @param string $password  The password (plain text)
   * @return mixed[]          A new User instance corresponding to the new account
   * @throws Exception        If the insertion in the DB fails (SQL error is provided)
   */
   
   public static function insert($pseudo, $email, $password)
   {
      $currentDate = Utils::toDatetime(Utils::SQLServerTime());
      $secret = substr(md5(uniqid(rand(), true)), 0, 15);
      $confirmationKey = substr(md5(uniqid(rand(), true)), 15, 15);
      $newLine = array('pseudo' => $pseudo,
      'email' => $email,
      'secret' => $secret,
      'password' => sha1($pseudo.$secret.$password),
      'confirmation' => $confirmationKey,
      'registration_date' => $currentDate,
      'last_connection' => $currentDate,
      'advanced_features' => 'no',
      'function_pseudo' => NULL,
      'pwd_reset_attempts' => 0,
      'pwd_reset_last_attempt' => '1970-01-01 00:00:00',
      'last_ban_expiration' => '1970-01-01 00:00:00',
      'using_preferences' => 'no',
      'pref_message_size' => 'default',
      'pref_posts_per_page' => 20,
      'pref_video_default_display' => 'thumbnail', 
      'pref_video_thumbnail_style' => 'hq', 
      'pref_default_nav_mode' => 'classic', 
      'pref_auto_preview' => 'no', 
      'pref_auto_refresh' => 'no');
      
      $sql = "INSERT INTO users VALUES(:pseudo, 
                                       :email, 
                                       :secret, 
                                       :password, 
                                       :confirmation, 
                                       :registration_date, 
                                       :last_connection, 
                                       :advanced_features, 
                                       :function_pseudo, 
                                       :pwd_reset_attempts, 
                                       :pwd_reset_last_attempt, 
                                       :last_ban_expiration,
                                       :using_preferences,
                                       :pref_message_size, 
                                       :pref_posts_per_page, 
                                       :pref_video_default_display, 
                                       :pref_video_thumbnail_style, 
                                       :pref_default_nav_mode, 
                                       :pref_auto_preview, 
                                       :pref_auto_refresh)";
      
      $res = Database::secureWrite($sql, $newLine);
      if($res != NULL)
         throw new Exception('New user account could not be created: '. $res[2]);
      
      return new User($newLine);
   }
   
   /*
   * Static method to now if a given pseudonym is already used or not.
   *
   * @param string $pseudo  The pseudonym that needs to be tested
   * @return bool           True if the pseudonym is already used, false otherwise
   * @throws Exception      If an error occurs while checking availability (SQL error is provided)
   */
   
   public static function isPseudoUsed($pseudo)
   {
      $res = Database::secureRead("SELECT COUNT(*) AS nb FROM users WHERE pseudo=? OR function_pseudo=?", array($pseudo, $pseudo), true);
      
      if(sizeof($res) == 3)
         throw new Exception('Could not check the availability of the pseudonym: '. $res[2]);
      
      if($res['nb'] > 0)
         return true;
      return false;
   }
   
   /*
   * Variant to check if a pseudo exists as regular user.
   *
   * @param string $pseudo  The pseudonym that needs to be tested
   * @return bool           True if the pseudonym is used for a regular account, false otherwise
   * @throws Exception      If an error occurs while checking availability (SQL error is provided)
   */
   
   public static function userExists($pseudo)
   {
      $res = Database::secureRead("SELECT COUNT(*) AS nb FROM users WHERE pseudo=?", array($pseudo), true);
      
      if(sizeof($res) == 3)
         throw new Exception('Could not check the existence of the given user: '. $res[2]);
      
      if($res['nb'] > 0)
         return true;
      return false;
   }
   
   /*
   * Static method to now if a given e-mail address is already used or not.
   *
   * @param string $email  The e-mail address that needs to be tested
   * @return bool          True if the address is already used, false otherwise
   * @throws Exception     If an error occurs while checking availability (SQL error is provided)
   */
   
   public static function isEmailUsed($email)
   {
      $res = Database::secureRead("SELECT COUNT(*) AS nb FROM users WHERE email=?", array($email), true);
      
      if(sizeof($res) == 3)
         throw new Exception('Could not check the availability of the e-mail address: '. $res[2]);
      
      if($res['nb'] == 1)
         return true;
      return false;
   }
   
   // Accessers
   public function get($field) { return $this->_data[$field]; }
   public function getAll() { return $this->_data; }
   
   /*
   * Method to change the password (this is done separately from e-mail edition).
   *
   * @param $password   The new password (plain text)
   * @throws Exception  If an error occurs while updating this user (SQL error is provided)
   */
   
   public function setPassword($password)
   {
      $newPassword = sha1($this->_data['pseudo'].$this->_data['secret'].$password);
      $res = Database::secureWrite("UPDATE users SET password=? WHERE pseudo=?",
                         array($newPassword, $this->_data['pseudo']));
      
      if($res != NULL)
         throw new Exception('User could not be updated: '. $res[2]);
      
      $this->_data['password'] = $newPassword;
   }
   
   /*
   * Method to change the e-mail address. After changing his/her address, the user must confirm
   * his/her account again. Therefore, a new confirmation key is generated (but with a different
   * length, in order to distingish the new account keys from the e-mail switching keys). The
   * string stored in the "email" field is also formatted to contain both the old and the new
   * addresses.
   *
   * @param $email      The new e-mail address
   * @throws Exception  If an error occurs while updating this user (SQL error is provided)
   */
   
   public function editEmail($email)
   {
      $currentDate = Utils::toDatetime(Utils::SQLServerTime());
      $confirmationKey = substr(md5(uniqid(rand(), true)), 0, 10);
      $newEmailField = $this->_data['email'] .'|'. $email;
      $res = Database::secureWrite("UPDATE users SET email=?, confirmation=?, last_connection=? WHERE pseudo=?",
                         array($newEmailField, $confirmationKey, $currentDate, $this->_data['pseudo']));
      
      if($res != NULL)
         throw new Exception('User could not be updated: '. $res[2]);
      
      $this->_data['email'] = $newEmailField;
      $this->_data['confirmation'] = $confirmationKey;
   }
   
   /*
   * Method to finish the e-mail address switch. Called when the user has successfully confirmed
   * his new address.
   *
   * @throws Exception  If an error occurs while updating this user (SQL error is provided)
   */
   
   public function confirmEmail()
   {
      if(strpos($this->_data['email'], '|') == FALSE)
         return;
      
      $exploded = explode('|', $this->_data['email']);
      $newEmail = $exploded[1];
      $res = Database::secureWrite("UPDATE users SET email=?, confirmation='DONE' WHERE pseudo=?",
                         array($newEmail, $this->_data['pseudo']));
      
      if($res != NULL)
         throw new Exception('User could not be updated: '. $res[2]);
      
      $this->_data['email'] = $newEmail;
      $this->_data['confirmation'] = 'DONE';
   }
   
   /*
   * Method to abort the e-mail address switch. This is useful if the user has entered a wrong
   * address; after a while, this method will be called on this user so his account can be used
   * again (but with the old e-mail address).
   *
   * @throws Exception  If an error occurs while updating this user (SQL error is provided)
   */
   
   public function abortEmailEdition()
   {
      if(strpos($this->_data['email'], '|') != FALSE)
         return;
      
      $exploded = explode('|', $this->_data['email']);
      $newEmail = $exploded[0];
      $res = Database::secureWrite("UPDATE users SET email=?, confirmation='DONE' WHERE pseudo=?",
                         array($newEmail, $this->_data['pseudo']));
      
      if($res != NULL)
         throw new Exception('User could not be updated: '. $res[2]);
      
      $this->_data['email'] = $newEmail;
      $this->_data['confirmation'] = 'DONE';
   }
   
   /*
   * Method to confirm a new account. Note that the method does not check the confirmation key (it
   * is up to the calling code to check it), and just updates the account so the user can log in.
   *
   * @throws Exception  If an error occurs while updating this user (SQL error is provided)
   */
   
   public function confirm()
   {
      if($this->_data['confirmation'] == 'DONE')
         return;
      
      $res = Database::secureWrite("UPDATE users SET confirmation='DONE' WHERE pseudo=?", array($this->_data['pseudo']));
      
      if($res != NULL)
         throw new Exception('User could not be updated: '. $res[2]);
      
      $this->_data['confirmation'] = 'DONE';
   }
   
   /*
   * Method to update the user's preferences regarding content display (e.g., how many messages 
   * are displayed in a page of a given topic). The preferences must be formatted as an array 
   * containing the following fields:
   *
   * -using_preferences ('yes' or 'no')
   * -message_size ('default' or 'medium')
   * -posts_per_page (integer between 5 and 100 included)
   * -video_default_display ('embedded' or 'thumbnail')
   * -video_thumbnail_style ('hq' or 'small')
   *
   * N.B.: it's up to the calling code to ensure the values match the above description.
   *
   * @param mixed[] $arg   The formatted preferences
   * @throws Exception     If an error occurs while updating the user (SQL error provided)
   */
   
   public function updatePreferences($arg)
   {
      $arg['user_pseudo'] = $this->_data['pseudo'];
      
      $sql = "UPDATE users SET
      using_preferences=:using_preferences, 
      pref_message_size=:message_size, 
      pref_posts_per_page=:posts_per_page, 
      pref_video_default_display=:video_default_display, 
      pref_video_thumbnail_style=:video_thumbnail_style, 
      pref_default_nav_mode=:default_nav_mode, 
      pref_auto_preview=:auto_preview, 
      pref_auto_refresh=:auto_refresh 
      WHERE pseudo=:user_pseudo";
      
      $res = Database::secureWrite($sql, $arg);
      
      if($res != NULL)
         throw new Exception('User could not be updated: '. $res[2]);
      
      $this->_data['using_preferences'] = $arg['using_preferences'];
      $this->_data['pref_message_size'] = $arg['message_size'];
      $this->_data['pref_posts_per_page'] = $arg['posts_per_page'];
      $this->_data['pref_video_default_display'] = $arg['video_default_display'];
      $this->_data['pref_video_thumbnail_style'] = $arg['video_thumbnail_style'];
      $this->_data['pref_default_nav_mode'] = $arg['default_nav_mode'];
      $this->_data['pref_auto_preview'] = $arg['auto_preview'];
      $this->_data['pref_auto_refresh'] = $arg['auto_refresh'];
   }
   
   /*
   * Method to (de)activate the advanced posting features. The method writes in the DB the 
   * opposite of the current state.
   *
   * @throws Exception  If an error occurs while updating this user (SQL error is provided)
   * @return bool       True if the field "advanced_features" is set to "yes", False otherwise
   */
   
   public function updateAdvancedFeatures()
   {
      if($this->_data['confirmation'] != 'DONE')
         return;
      
      $newValue = 'yes';
      if($this->_data['advanced_features'] === 'yes')
         $newValue = 'no';
      
      $res = Database::secureWrite("UPDATE users SET advanced_features=? WHERE pseudo=?", 
                         array($newValue, $this->_data['pseudo']));
      
      if($res != NULL)
         throw new Exception('User could not be updated: '. $res[2]);
      
      $this->_data['advanced_features'] = $newValue;
      
      return ($newValue === 'yes');
   }
   
   /*
   * Boolean method telling if they were attempts to reset password of this account during the last
   * 24 hours.
   *
   * @return bool  True if someone attempted to reset the password in the last 24h, false otherwise
   */
   
   public function areThereRecentPwdReset()
   {
      $currentTime = Utils::SQLServerTime();
      $delay = $currentTime - Utils::toTimestamp($this->_data['pwd_reset_last_attempt']);
      if($delay < 86400)
         return true;
      return false;
   }
   
   /*
   * Method to increment the counter of password reset attempts. It is reset if the last
   * attempt was done more than 24 hours ago. Nothing is returned and there is no parameter.
   *
   * @throws Exception  If an error occurs while updating this user (SQL error is provided)
   */
   
   public function incPwdResetAttempts()
   {
      $newAmount = $this->_data['pwd_reset_attempts'] + 1;
      if(!$this->areThereRecentPwdReset())
         $newAmount = 1;
   
      $currentDate = Utils::toDatetime(Utils::SQLServerTime());
      $input = array($newAmount, $currentDate, $this->_data['pseudo']);
      $res = Database::secureWrite("UPDATE users SET pwd_reset_attempts=?, pwd_reset_last_attempt=? WHERE pseudo=?", $input);
      
      if($res != NULL)
         throw new Exception('User could not be updated: '. $res[2]);
      
      $this->_data['pwd_reset_attempts'] = $newAmount;
      $this->_data['pwd_reset_last_attempt'] = $currentDate;
   }
   
   /*
   * Method to temporarily ban an account. If the user is already banned, the new duration is 
   * used to postpone the expiration date.
   *
   * @param number $duration  The duration, in seconds, of the banishment
   * @throws Exception        If some SQL error occurs during the operation
   */
   
   public function banish($duration)
   {
      $base = Utils::SQLServerTime();
      if(Utils::toTimestamp($this->_data['last_ban_expiration']) > $base)
         $base = Utils::toTimestamp($this->_data['last_ban_expiration']);
   
      $expiration = Utils::toDatetime($base + $duration);
      $input = array($expiration, $this->_data['pseudo']);
      $res = Database::secureWrite("UPDATE users SET last_ban_expiration=? WHERE pseudo=?", $input);
      
      if($res != NULL)
         throw new Exception('User could not be updated: '. $res[2]);
      
      $this->_data['last_ban_expiration'] = $expiration;
   }
   
   /*
   * Method to relax this user if banished. The last_ban_expiration field is reset to the current 
   * time such that this user can log in again.
   *
   * @throws Exception   If some SQL error occurs during the operation
   */
   
   public function relax()
   {
      $user = $this->_data['pseudo'];
      $expiration = Utils::toDatetime(Utils::SQLServerTime());
      $input = array($expiration, $this->_data['pseudo']);
      $res = Database::secureWrite("UPDATE users SET last_ban_expiration=? WHERE pseudo=?", $input);
      
      if($res != NULL)
         throw new Exception('User could not be updated: '. $res[2]);
      
      $this->_data['last_ban_expiration'] = $expiration;
   }
   
   /*
   * Method to record a banshiment sentence. This operation is separated from the operation 
   * above to make the code clearer at the calling script (since it is performed in a single 
   * transaction, having separate methods highlights the fact that there are multiple requests). 
   * Nothing is returned upon success.
   *
   * N.B.: it is assumed this method is called only after a successful call to banish() and under 
   * a function account.
   *
   * @param number $duration  The duration, in seconds, of the banishment
   * @param string $motif     The reason behind this banishment (showed upon connection attempt)
   * @throws Exception        If some SQL error occurs during the operation
   */
   
   public function recordSentence($duration, $motif)
   {
      $expirationDate = $this->_data['last_ban_expiration']; // Because recordSentence() always takes place after a call of banish()
      $currentDate = Utils::toDatetime(Utils::SQLServerTime());
      $newLine = array('pseudo' => $this->_data['pseudo'],
      'judge' => LoggedUser::$data['function_pseudo'],
      'date' => $currentDate,
      'duration' => $duration,
      'expiration' => $expirationDate,
      'details' => $motif);
      
      $res = Database::secureWrite("INSERT INTO records_sentences VALUES(:pseudo,:judge,:date,:duration,:expiration,'no',:details)", $newLine);
      if($res != NULL)
         throw new Exception('New record could not be created: '. $res[2]);
   }
   
   /*
   * Method to relax active sentences. The motivation behind the separation from relax() is the 
   * same as for recordSentence().
   *
   * @throws Exception   If some SQL error occurs during the operation
   */
   
   public function relaxSentences()
   {
      // Relaxes active sentences
      $res = Database::secureWrite("UPDATE records_sentences 
                          SET relaxed='yes'
                          WHERE pseudo=? AND expiration > ?",
                          array($this->_data['pseudo'], Utils::toDatetime(Utils::SQLServerTime())));
                          
      if($res != NULL)
         throw new Exception('Sentences could not be relaxed: '. $res[2]);
   }
   
   /*
   * Method to retrieve banishment sentences for this user.
   *
   * @param bool $all   To set to false if we only want the *active* sentences; otherwise, all 
   *                    sentences are being listed (optional, default is true)
   * @returns mixed[]   An array containing all entries from "records_banishment" for this user
   * @throws Exception  If some SQL error occurs while retrieving data
   */
   
   public function listSentences($all = true)
   {
      $user = $this->_data['pseudo'];
      
      $res = NULL;
      if(!$all)
      {
         $res = Database::secureRead("SELECT judge, date, duration, expiration, relaxed, details 
                            FROM records_sentences 
                            WHERE pseudo=? AND expiration > ? AND relaxed='no'
                            ORDER BY date",
                            array($user, Utils::toDatetime(Utils::SQLServerTime())), false);
      }
      else
      {
         $res = Database::secureRead("SELECT judge, date, duration, expiration, relaxed, details 
                            FROM records_sentences 
                            WHERE pseudo=? 
                            ORDER BY date",
                            array($user), false);
      }
      
      if($res != NULL && !is_array($res[0]) && sizeof($res) == 3)
         throw new Exception('Could not obtain records for '.$user.': '. $res[2]);
      
      return $res;
   }
   
   /*
   * Counts the number of published articles written by this user. The method can be used "as is" 
   * with no argument at all, but 2 parameters can be used to count the amount of articles 
   * before/after a given (publication) date. Both must be provided together.
   *
   * @param string $date         The given date (optional)
   * @param bool $beforeOrAfter  True if we count articles before that date, false for after
   * @return number              The amount of published articles
   * @throws Exception           If the articles could not be found
   */
   
   public function countArticles($date = '1970-01-01 00:00:00', $beforeOrAfter = true)
   {
      if(Utils::toTimestamp($date) > 0)
      {
         if($beforeOrAfter)
         {
            $sql = 'SELECT COUNT(*) AS nb FROM articles WHERE pseudo=? && date_publication < ?';
         }
         else
         {
            $sql = 'SELECT COUNT(*) AS nb FROM articles WHERE pseudo=? ';
            $sql .= '&& date_publication != \'1970-01-01 00:00:00\' && date_publication > ?';
         }
         $arg = array($this->_data['pseudo'], $date);
      }
      else
      {
         $sql = 'SELECT COUNT(*) AS nb FROM articles WHERE pseudo=? && ';
         $sql .= 'date_publication != \'1970-01-01 00:00:00\'';
         $arg = array($this->_data['pseudo']);
      }
      
      $res = Database::secureRead($sql, $arg, true);
      
      if(sizeof($res) == 3)
         throw new Exception('Articles could not be found: '. $res[2]);
      else if($res == NULL)
         return 0;
      
      return $res['nb'];
   }
   
   /*
   * Gets a set of published articles from this user. The result is a 2D array. Unlike other 
   * "getItems" method, the $first and $nb parameters are optional (for now).
   *
   * @param number $first     The index of the first article of the set (optional)
   * @param number $nb        The maximum amount of articles to retrieve (optional, if 0 all 
   *                          articles from this user will be fetched)
   * @return mixed[]          The articles that were found
   * @throws Exception        If articles could not be found (SQL error is provided) or if no 
   *                          article could be found
   */
   
   public function getArticles($first = 0, $nb = 0)
   {
      $sql = 'SELECT * FROM articles WHERE pseudo=? && date_publication!=\'1970-01-01 00:00:00\'';
      $sql .= ' ORDER BY date_publication DESC';
      if($nb > 0)
         $sql .= ' LIMIT '.$first.','.$nb;
   
      $res = Database::secureRead($sql, array($this->_data['pseudo']));
      
      if($res != NULL && !is_array($res[0]) && sizeof($res) == 3)
         throw new Exception('Articles could not be found: '. $res[2]);
      else if($res == NULL)
         throw new Exception('No article has been found.');
      
      return $res;
   }
   
   /*
   * Counts the number of messages posted by this user. The method can be used "as is" with no
   * argument at all, but 2 parameters can be used to count the amount of messages before/after a
   * given date. Both must be provided together.
   *
   * @param string $date         The given date (optional)
   * @param bool $beforeOrAfter  True if we count messages before that date, false for after
   * @return number              The amount of messages
   * @throws Exception           If the messages could not be found
   */
   
   public function countPosts($date = '1970-01-01 00:00:00', $beforeOrAfter = true)
   {
      if(Utils::toTimestamp($date) > 0)
      {
         if($beforeOrAfter)
            $sql = 'SELECT COUNT(*) AS nb FROM posts WHERE author=? && id_topic=? && date < ?';
         else
            $sql = 'SELECT COUNT(*) AS nb FROM posts WHERE author=? && id_topic=? && date > ?';
         $arg = array($this->_data['pseudo'], $this->_data['id_topic'], $date);
      }
      else
      {
         $sql = 'SELECT COUNT(*) AS nb FROM posts WHERE author=?';
         $arg = array($this->_data['pseudo']);
      }
      
      $res = Database::secureRead($sql, $arg, true);
      
      if(sizeof($res) == 3)
         throw new Exception('Messages could not be found: '. $res[2]);
      else if($res == NULL)
         return 0;
      
      return $res['nb'];
   }
   
   /*
   * Gets a set of messages from this user, provided an index (first message of the set) and an
   * amount (number of messages to retrieve). The result is a 2D array. An additionnal boolean 
   * can be used to get the messages in anti-chronological order (default is chronological).
   *
   * @param number $first     The index of the first message of the set
   * @param number $nb        The maximum amount of messages to retrieve
   * @param boolean $reverse  True if the messages should be obtained in anti-chronological order
   *                          (true by default)
   * @return mixed[]          The messages that were found
   * @throws Exception        If messages could not be found (SQL error is provided) or if no 
   *                          message could be found with the given criteria
   */
   
   public function getPosts($first, $nb, $reverse = true)
   {
      $order = 'date';
      if($reverse)
         $order .= ' DESC';
      
      $sql = 'SELECT * FROM posts WHERE author=? ORDER BY '.$order.' LIMIT '.$first.','.$nb;
   
      $res = Database::secureRead($sql, array($this->_data['pseudo']));
      
      if($res != NULL && !is_array($res[0]) && sizeof($res) == 3)
         throw new Exception('Messages could not be found: '. $res[2]);
      else if($res == NULL)
         throw new Exception('No message has been found.');
      
      return $res;
   }
   
   /*
   * Counts the number of topics that were favorited by this user.
   *
   * @return number     The amount of topics favorited by this user
   * @throws Exception  If some SQL error occurs (SQL error is provided)
   */
   
   public function countFavoritedTopics()
   {
      $sql = 'SELECT COUNT(*) AS nb FROM map_topics_users WHERE favorite=\'yes\' && pseudo=?';
   
      $res = Database::secureRead($sql, array($this->_data['pseudo']), true);
      
      if(sizeof($res) == 3)
         throw new Exception('Topics could not be counted: '. $res[2]);
      
      return $res['nb'];
   }
   
   /*
   * Gets a set of topics that were favorited by this user in the same fashion as getTopics() in 
   * Topic class; the SQL request is just a bit longer.
   *
   * @param number $first  The index of the first topic of the set
   * @param number $nb     The maximum amount of topics to list
   * @return mixed[]       The topics that were found
   * @throws Exception     If topics could not be found (SQL error is provided)
   */
   
   public function getFavoritedTopics($first, $nb)
   {
      $sql = 'SELECT topics.*, t_nb.nb FROM topics NATURAL JOIN (
      SELECT id_topic, COUNT(*) AS nb FROM posts GROUP BY id_topic
      ) t_nb NATURAL JOIN map_topics_users WHERE favorite=\'yes\' && pseudo=? 
      ORDER BY last_post DESC LIMIT '.$first.','.$nb;
   
      $res = Database::secureRead($sql, array($this->_data['pseudo']));
      
      if(!is_array($res[0]) && sizeof($res) == 3)
         throw new Exception('Topics could not be listed: '. $res[2]);
      
      return $res;
   }
   
   /*
   * Static method to get the total number of users.
   *
   * @return number     The total number of users
   * @throws Exception  If users could ne counted (SQL error is provided)
   */
   
   public static function countUsers()
   {
      $sql = 'SELECT COUNT(*) AS nb FROM users WHERE LENGTH(confirmation) != 15';
      
      $res = Database::hardRead($sql, true);
      
      if(sizeof($res) == 3)
         throw new Exception('Users could not be counted: '. $res[2]);
      
      return $res['nb'];
   }
   
   /*
   * Static method to obtain a set of users in a similar fashion to getTopics() in Topic class. 
   * Users are listed in alphabetical order.
   *
   * @param number $first  The index of the first user of the set
   * @param number $nb     The maximum amount of users to list
   * @return mixed[]       The users who were found
   * @throws Exception     If users could not be found (SQL error is provided)
   */

   public static function getUsers($first, $nb)
   {
      $sql = 'SELECT * 
      FROM users 
      WHERE LENGTH(confirmation) != 15 
      ORDER BY pseudo DESC LIMIT '.$first.','.$nb;
   
      $res = Database::hardRead($sql);
      
      if(!is_array($res[0]) && sizeof($res) == 3)
         throw new Exception('Users could not be listed: '. $res[2]);
      
      return $res;
   }
   
   /*
   * Static method to look for up to 5 users (given as pseudonyms) containing a given string 
   * labelled as $needle (just like in string functions from the PHP library).
   *
   * @param string $needle  A string (without | or ")
   * @return string[]       An array of pseudonyms containing $needle, in alphabetical order
   * @throws Exception      If some error occurs with SQL server (SQL error is provided)
   */
   
   public static function findUsers($needle)
   {
      $searchInput = array('needle' => '%'.strtolower($needle).'%');
      $sql = "SELECT pseudo 
      FROM users 
      WHERE LOWER(pseudo) LIKE :needle && LENGTH(confirmation) != 15 
      ORDER BY pseudo LIMIT 5";
      $res = Database::secureRead($sql, $searchInput);
      
      if($res != NULL && !is_array($res[0]))
      {
         throw new Exception('Could not find tags: '. $res[2]);
      }
      
      // Converts results into a linear array (results are given as a 2D array)
      $output = array();
      $nbResults = count($res);
      for($i = 0; $i < $nbResults; $i++)
         array_push($output, $res[$i]['pseudo']);

      return $output;
   }
   
   /*
   * Static method to check if selected users are currently browsing the site or not (i.e., the 
   * date and time in the "last_connection" field is less than 3 minutes ago).
   *
   * @param string users[]   An array of strings with the pseudonymes of the users to check
   * @param string admins[]  The same, but for "function" pseudonyms (optional)
   * @param string[]         An array containing the pseudonyms of the selected users who are 
   *                         currently online (i.e., last activity is less than 3 minutes ago)
   * @throws Exception       If some error occurs with SQL server (SQL error is provided)
   */
   
   public static function checkOnlineStatus($users, $admins = null)
   {
      $cond1 = ($users == null || !is_array($users) || count($users) == 0);
      $cond2 = ($admins == null || !is_array($admins) || count($admins) == 0);
      if($cond1 && $cond2)
         return null;
   
      $sql = 'SELECT pseudo, last_connection, function_pseudo  
      FROM users 
      WHERE ';
      
      $input = array();
   
      // Formats $users for the SQL request
      if(!$cond1)
      {
         $formattedUsers = '';
         for($i = 0; $i < count($users); $i++)
         {
            if($i > 0)
               $formattedUsers .= ',';
            $formattedUsers .= '?';
            array_push($input, $users[$i]);
         }
         $sql .= 'pseudo IN ('.$formattedUsers.')';
      }
      
      if(!$cond1 && !$cond2)
         $sql .= 'OR ';
      
      // Add admins bit if necessary
      if(!$cond2)
      {
         $formattedAdmins = '';
         for($i = 0; $i < count($admins); $i++)
         {
            if($i > 0)
               $formattedAdmins .= ',';
            $formattedAdmins .= '?';
            array_push($input, $admins[$i]);
         }
         $sql .= 'function_pseudo IN ('.$formattedAdmins.')';
      }
      
      $res = Database::secureRead($sql, $input);
      if($res != NULL && !is_array($res[0]))
      {
         throw new Exception('Could not check online presence: '. $res[2]);
      }
      
      // Produces the output
      $output = array();
      $timestampNow = Utils::SQLServerTime();
      for($i = 0; $i < count($res); $i++)
      {
         $timestampThen = Utils::toTimestamp($res[$i]['last_connection']);
         if(($timestampNow - $timestampThen) < 180)
         {
            array_push($output, $res[$i]['pseudo']);
            if(!$cond2 && strlen($res[$i]['function_pseudo']) > 2)
               array_push($output, $res[$i]['function_pseudo']);
         }
      }
      
      return $output;
   }
   
   /*
   * --------------------
   * Registration process
   * --------------------
   */
   
   /*
   * Registers a presentation for a new account.
   *
   * @param string $presentation  The presentation given by the new user upon registration
   * @throws Exception            If some error occurs with SQL server (SQL error is provided)
   */
   
   public function registerPresentation($presentation)
   {
      $sql = 'INSERT INTO users_presentations VALUES(:pseudo, :pres)';
      $newLine = array('pseudo' => $this->_data['pseudo'], 'pres' => $presentation);
      
      $res = Database::secureWrite($sql, $newLine);
      if($res != NULL)
         throw new Exception('New user\'s presentation could not be registered: '. $res[2]);
   }
   
   /*
   * Gets the user's presentation written at registration, if there's one.
   *
   * @return string     The user's presentation, or an empty string if none
   * @throws Exception  If some error occurs with SQL server (SQL error is provided)
   */
   
   public function getPresentation()
   {
      $sql = 'SELECT presentation FROM users_presentations WHERE pseudo=?';
      $res = Database::secureRead($sql, array($this->_data['pseudo']), true);
      
      if($res == NULL)
         return '';
      else if($res != NULL && count($res) == 3)
         throw new Exception('User\'s presentation could not be fetched: '. $res[2]);
      
      return $res['presentation'];
   }
   
   /*
   * Gets the user's sponsor (i.e. the user who invited him/her), if there's one.
   *
   * @return string     The user's sponsor, or an empty string if none
   * @throws Exception  If some error occurs with SQL server (SQL error is provided)
   */
   
   public function getSponsor()
   {
      $sql = 'SELECT sponsor FROM invitations WHERE guest_email=?';
      $res = Database::secureRead($sql, array($this->_data['email']), true);
      
      if($res == NULL)
         return '';
      else if($res != NULL && count($res) == 3)
         throw new Exception('User\'s sponsor could not be fetched: '. $res[2]);
      
      return $res['sponsor'];
   }
   
   /*
   * Static method to delete unconfirmed accounts which were requested more than 24 hours ago. The 
   * SQL request used to this end ensure only new accounts are deleted (the length of the keys 
   * used for confirmation differs if the account is new or just changing e-mail).
   *
   * @throws Exception       If some error occurs with SQL server (SQL error is provided)
   */
   
   public static function cleanAccountRequests()
   {
      $oneDayAgo = Utils::toDatetime(Utils::SQLServerTime() - 86400);
      $sql = 'DELETE FROM users WHERE LENGTH(confirmation)=15 && registration_date < ?';
      $res = Database::secureWrite($sql, array($oneDayAgo));
      
      if($res != NULL && !is_array($res[0]))
         throw new Exception('Could not delete unused accounts: '. $res[2]);
   }
}

?>
