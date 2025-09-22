<?php
require_once __DIR__ . '/../../config/database.php';

class Profile {
    private $conn;
    private $table = 'profiles';

    public $id;
    public $user_id;
    public $first_name;
    public $last_name;
    public $date_of_birth;
    public $gender;
    public $interested_in;
    public $bio;
    public $location;
    public $latitude;
    public $longitude;
    public $max_distance;
    public $profile_picture;
    public $created_at;
    public $updated_at;

    public function __construct() {
        $database = Database::getInstance();
        $this->conn = $database->getConnection();
    }

    public function create() {
        $query = "INSERT INTO " . $this->table . " 
                  (user_id, first_name, last_name, date_of_birth, gender, interested_in, 
                   bio, location, latitude, longitude, max_distance, profile_picture) 
                  VALUES (:user_id, :first_name, :last_name, :date_of_birth, :gender, 
                          :interested_in, :bio, :location, :latitude, :longitude, 
                          :max_distance, :profile_picture)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':first_name', $this->first_name);
        $stmt->bindParam(':last_name', $this->last_name);
        $stmt->bindParam(':date_of_birth', $this->date_of_birth);
        $stmt->bindParam(':gender', $this->gender);
        $stmt->bindParam(':interested_in', $this->interested_in);
        $stmt->bindParam(':bio', $this->bio);
        $stmt->bindParam(':location', $this->location);
        $stmt->bindParam(':latitude', $this->latitude);
        $stmt->bindParam(':longitude', $this->longitude);
        $stmt->bindParam(':max_distance', $this->max_distance);
        $stmt->bindParam(':profile_picture', $this->profile_picture);
        
        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    public function update() {
        $query = "UPDATE " . $this->table . " 
                  SET first_name = :first_name, last_name = :last_name, 
                      date_of_birth = :date_of_birth, gender = :gender, 
                      interested_in = :interested_in, bio = :bio, 
                      location = :location, latitude = :latitude, 
                      longitude = :longitude, max_distance = :max_distance,
                      profile_picture = :profile_picture, updated_at = NOW()
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':id', $this->id);
        $stmt->bindParam(':first_name', $this->first_name);
        $stmt->bindParam(':last_name', $this->last_name);
        $stmt->bindParam(':date_of_birth', $this->date_of_birth);
        $stmt->bindParam(':gender', $this->gender);
        $stmt->bindParam(':interested_in', $this->interested_in);
        $stmt->bindParam(':bio', $this->bio);
        $stmt->bindParam(':location', $this->location);
        $stmt->bindParam(':latitude', $this->latitude);
        $stmt->bindParam(':longitude', $this->longitude);
        $stmt->bindParam(':max_distance', $this->max_distance);
        $stmt->bindParam(':profile_picture', $this->profile_picture);
        
        return $stmt->execute();
    }

    public function findByUserId($userId) {
        $query = "SELECT * FROM " . $this->table . " WHERE user_id = :user_id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $this->mapFromArray($stmt->fetch());
            return true;
        }
        return false;
    }

    public function getDiscoverProfiles($userId, $limit = 10) {
        // Get user's profile for matching criteria
        $userProfile = new Profile();
        if (!$userProfile->findByUserId($userId)) {
            return [];
        }

        // Get users this person has already swiped on
        $swipedQuery = "SELECT swiped_id FROM swipes WHERE swiper_id = :user_id";
        $swipedStmt = $this->conn->prepare($swipedQuery);
        $swipedStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $swipedStmt->execute();
        $swipedIds = $swipedStmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Add current user to excluded list
        $swipedIds[] = $userId;
        $placeholders = str_repeat('?,', count($swipedIds) - 1) . '?';

        $query = "SELECT p.*, u.email, u.last_login,
                         (6371 * acos(cos(radians(?)) * cos(radians(p.latitude)) * 
                          cos(radians(p.longitude) - radians(?)) + 
                          sin(radians(?)) * sin(radians(p.latitude)))) AS distance
                  FROM " . $this->table . " p
                  JOIN users u ON p.user_id = u.id
                  WHERE u.is_active = 1 
                    AND u.email_verified = 1
                    AND p.user_id NOT IN ($placeholders)
                    AND p.gender IN (" . $this->getInterestedInFilter($userProfile->interested_in) . ")
                    AND p.interested_in IN (" . $this->getGenderFilter($userProfile->gender) . ")
                    AND TIMESTAMPDIFF(YEAR, p.date_of_birth, CURDATE()) BETWEEN ? AND ?
                  HAVING distance <= ?
                  ORDER BY u.last_login DESC, distance ASC
                  LIMIT ?";

        $stmt = $this->conn->prepare($query);
        
        $params = [
            $userProfile->latitude,
            $userProfile->longitude,
            $userProfile->latitude,
            ...$swipedIds,
            MIN_AGE,
            MAX_AGE,
            $userProfile->max_distance,
            $limit
        ];

        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    private function getInterestedInFilter($interestedIn) {
        switch ($interestedIn) {
            case 'male':
                return "'male'";
            case 'female':
                return "'female'";
            case 'both':
                return "'male', 'female'";
            default:
                return "'male', 'female'";
        }
    }

    private function getGenderFilter($gender) {
        return "'both', '$gender'";
    }

    public function getAge() {
        if (!$this->date_of_birth) return null;
        
        $birthDate = new DateTime($this->date_of_birth);
        $today = new DateTime();
        return $today->diff($birthDate)->y;
    }

    private function mapFromArray($data) {
        $this->id = $data['id'];
        $this->user_id = $data['user_id'];
        $this->first_name = $data['first_name'];
        $this->last_name = $data['last_name'];
        $this->date_of_birth = $data['date_of_birth'];
        $this->gender = $data['gender'];
        $this->interested_in = $data['interested_in'];
        $this->bio = $data['bio'];
        $this->location = $data['location'];
        $this->latitude = $data['latitude'];
        $this->longitude = $data['longitude'];
        $this->max_distance = $data['max_distance'];
        $this->profile_picture = $data['profile_picture'];
        $this->created_at = $data['created_at'];
        $this->updated_at = $data['updated_at'];
    }

    public function toArray() {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'date_of_birth' => $this->date_of_birth,
            'age' => $this->getAge(),
            'gender' => $this->gender,
            'interested_in' => $this->interested_in,
            'bio' => $this->bio,
            'location' => $this->location,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'max_distance' => $this->max_distance,
            'profile_picture' => $this->profile_picture,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}
?>