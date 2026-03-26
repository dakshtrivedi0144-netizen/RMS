<?php
class LearningPath {
    private $conn;
    private $table_name = "learning_paths";

    public $id;
    public $user_id;
    public $title;
    public $description;
    public $category;
    public $difficulty;
    public $topics = [];
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                 (user_id, title, description, category, difficulty, created_at, updated_at) 
                  VALUES 
                 (:user_id, :title, :description, :category, :difficulty, :created_at, :updated_at)";

        $stmt = $this->conn->prepare($query);

        // Sanitize data
        $this->user_id = htmlspecialchars(strip_tags($this->user_id));
        $this->title = htmlspecialchars(strip_tags($this->title));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->category = htmlspecialchars(strip_tags($this->category));
        $this->difficulty = htmlspecialchars(strip_tags($this->difficulty));
        $this->created_at = date('Y-m-d H:i:s');
        $this->updated_at = $this->created_at;

        // Bind parameters
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":title", $this->title);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":category", $this->category);
        $stmt->bindParam(":difficulty", $this->difficulty);
        $stmt->bindParam(":created_at", $this->created_at);
        $stmt->bindParam(":updated_at", $this->updated_at);

        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            
            // Save topics
            if (!empty($this->topics)) {
                $this->saveTopics();
            }
            
            return true;
        }
        return false;
    }

    private function saveTopics() {
        // First, delete existing topics for this learning path
        $deleteQuery = "DELETE FROM learning_path_topics WHERE learning_path_id = :learning_path_id";
        $deleteStmt = $this->conn->prepare($deleteQuery);
        $deleteStmt->bindParam(":learning_path_id", $this->id);
        $deleteStmt->execute();

        // Insert new topics
        $query = "INSERT INTO learning_path_topics (learning_path_id, topic, sort_order) VALUES ";
        $inserts = [];
        $values = [];
        $i = 1;
        
        foreach ($this->topics as $index => $topic) {
            $topic = htmlspecialchars(strip_tags($topic));
            $sortOrder = $index + 1;
            $inserts[] = "(:learning_path_id, :topic_" . $i . ", :sort_order_" . $i . ")";
            $values[":topic_" . $i] = $topic;
            $values[":sort_order_" . $i] = $sortOrder;
            $i++;
        }
        
        if (!empty($inserts)) {
            $query .= implode(", ", $inserts);
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":learning_path_id", $this->id);
            
            foreach ($values as $key => &$val) {
                $stmt->bindParam($key, $val);
            }
            
            $stmt->execute();
        }
    }

    public function getById($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $this->id = $row['id'];
            $this->user_id = $row['user_id'];
            $this->title = $row['title'];
            $this->description = $row['description'];
            $this->category = $row['category'];
            $this->difficulty = $row['difficulty'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            
            // Get topics
            $this->getTopics();
            
            return true;
        }
        return false;
    }

    private function getTopics() {
        $query = "SELECT topic FROM learning_path_topics 
                 WHERE learning_path_id = :learning_path_id 
                 ORDER BY sort_order ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":learning_path_id", $this->id);
        $stmt->execute();
        
        $this->topics = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $this->topics[] = $row['topic'];
        }
    }

    public function update() {
        $query = "UPDATE " . $this->table_name . " SET 
                    title = :title,
                    description = :description,
                    category = :category,
                    difficulty = :difficulty,
                    updated_at = :updated_at
                  WHERE id = :id AND user_id = :user_id";

        $stmt = $this->conn->prepare($query);

        // Sanitize data
        $this->title = htmlspecialchars(strip_tags($this->title));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->category = htmlspecialchars(strip_tags($this->category));
        $this->difficulty = htmlspecialchars(strip_tags($this->difficulty));
        $this->updated_at = date('Y-m-d H:i:s');

        // Bind parameters
        $stmt->bindParam(":title", $this->title);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":category", $this->category);
        $stmt->bindParam(":difficulty", $this->difficulty);
        $stmt->bindParam(":updated_at", $this->updated_at);
        $stmt->bindParam(":id", $this->id);
        $stmt->bindParam(":user_id", $this->user_id);

        if($stmt->execute()) {
            // Update topics
            if (!empty($this->topics)) {
                $this->saveTopics();
            }
            return true;
        }
        return false;
    }

    public function delete() {
        // First delete topics
        $deleteTopicsQuery = "DELETE FROM learning_path_topics WHERE learning_path_id = :learning_path_id";
        $deleteTopicsStmt = $this->conn->prepare($deleteTopicsQuery);
        $deleteTopicsStmt->bindParam(":learning_path_id", $this->id);
        $deleteTopicsStmt->execute();
        
        // Then delete the learning path
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id AND user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        $stmt->bindParam(":user_id", $this->user_id);
        
        return $stmt->execute();
    }

    public function getAllByUser($user_id, $filters = []) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE user_id = :user_id";
        $params = [":user_id" => $user_id];
        
        // Apply filters
        if (!empty($filters['category'])) {
            $query .= " AND category = :category";
            $params[":category"] = $filters['category'];
        }
        
        if (!empty($filters['difficulty'])) {
            $query .= " AND difficulty = :difficulty";
            $params[":difficulty"] = $filters['difficulty'];
        }
        
        $query .= " ORDER BY created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => &$val) {
            $stmt->bindParam($key, $val);
        }
        
        $stmt->execute();
        
        $learning_paths = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $learning_path = [
                'id' => $row['id'],
                'title' => $row['title'],
                'description' => $row['description'],
                'category' => $row['category'],
                'difficulty' => $row['difficulty'],
                'created_at' => $row['created_at'],
                'updated_at' => $row['updated_at']
            ];
            
            // Get topics for this learning path
            $topicsQuery = "SELECT topic FROM learning_path_topics 
                           WHERE learning_path_id = :learning_path_id 
                           ORDER BY sort_order ASC";
            $topicsStmt = $this->conn->prepare($topicsQuery);
            $topicsStmt->bindParam(":learning_path_id", $row['id']);
            $topicsStmt->execute();
            
            $topics = [];
            while ($topicRow = $topicsStmt->fetch(PDO::FETCH_ASSOC)) {
                $topics[] = $topicRow['topic'];
            }
            
            $learning_path['topics'] = $topics;
            $learning_paths[] = $learning_path;
        }
        
        return $learning_paths;
    }
}
?>
