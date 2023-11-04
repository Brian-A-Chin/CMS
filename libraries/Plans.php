<?php


    class plans {

        public int $id;
        public string $name;
        public float $price;
        public string $duration;
        public string $description;
        public int $active;


        public function __Construct($planId){
            $this->id = $planId;
            $this->set();
        }

        private function set():void{

            try {
                $query = 'SELECT * FROM plans WHERE id=? LIMIT 1';
                $statement = SQLServices::makeCoreConnection()->prepare($query);
                $statement->execute([$this->id]);
                $data = $statement->fetch(PDO::FETCH_ASSOC);

                $this->name = $data['name'];
                $this->price = $data['price'];
                $this->duration = $data['duration'];
                $this->description = $data['description'];
                $this->active = $data['active'];

            } catch (Exception $e) {
                BaseClass::logError([
                    'message' => 'Failed to get plan data',
                    'exception' => $e
                ]);
            }
        }

        public function getPlanInfo(bool $getActiveOnly = false): array | bool{

            try {
                if($getActiveOnly){
                    $query = 'SELECT * FROM plans WHERE id=? AND active=1 LIMIT 1';
                }else{
                    $query = 'SELECT * FROM plans WHERE id=? LIMIT 1';
                }
                $statement = SQLServices::makeCoreConnection()->prepare($query);
                $statement->execute([$this->id]);
                return $statement->fetch(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                BaseClass::logError([
                    'message' => 'Failed to get plan data',
                    'exception' => $e
                ]);
                return false;
            }
        }

        public static function getAll(): array{

            try {
                $query = 'SELECT * FROM plans where active=1';
                $statement = SQLServices::makeCoreConnection()->prepare($query);
                $statement->execute();
                return $statement->fetchAll();
            } catch (Exception $e) {
                BaseClass::logError([
                    'message' => 'Failed to get plan data',
                    'exception' => $e
                ]);
                return array();
            }
        }

        public static function getAllAdmin(): array{

            try {
                $query = 'SELECT * FROM plans where active!=0';
                $statement = SQLServices::makeCoreConnection()->prepare($query);
                $statement->execute();
                return $statement->fetchAll();
            } catch (Exception $e) {
                BaseClass::logError([
                    'message' => 'Failed to get plan data',
                    'exception' => $e
                ]);
                return array();
            }
        }


        public static function add(array $data) : int{

            try {
                $conn = SQLServices::makeCoreConnection();
                $smt = $conn->prepare("INSERT INTO `plans` (`name`,`price`,`duration`,`description`) VALUES (:name,:price,:duration,:description)");
                $smt->execute(array(
                    ":name" => $data['name'],
                    ":price" => $data['price'],
                    ":duration" => $data['duration'],
                    ":description" => $data['description'],
                ));
                return $conn->lastInsertId();
            } catch (Exception $e) {
                BaseClass::logError([
                    'message' => 'Failed to add plan',
                    'exception' => $e
                ]);
                return -1;
            }
        }

        public static function update(array $data):bool{
            try{
                SQLServices::makeCoreConnection()->prepare("UPDATE `plans` SET `name`=?,`price`=?,`duration`=?,`description`=?,`active`=? WHERE `id`=?")->execute([
                    $data['name'],
                    $data['price'],
                    $data['duration'],
                    $data['description'],
                    $data['active'],
                    $data['id'],
                ]);
                return true;
            }catch(Exception $e){
                BaseClass::logError([
                    'message' => 'Failed to update plan',
                    'exception' => $e
                ]);
                return false;
            }
        }

        public static function remove(int $id):void{
            SQLServices::makeCoreConnection()->prepare("DELETE FROM `plans` WHERE id=? LIMIT 1")->execute([$id]);
        }


    }