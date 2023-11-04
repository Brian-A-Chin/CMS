<?php


    class Subscriptions {

        public int $accountId;
        public string $planId;
        public string $name;
        public int $price;
        public string $duration;
        public string $description;
        public string $end;


        public function __Construct($accountId,$planId,$name,$price,$duration,$description,$end){
            $this->accountId = $accountId;
            $this->planId = $planId;
            $this->name = $name;
            $this->price = $price;
            $this->duration = $duration;
            $this->description = $description;
            $this->end = $end;
        }

        //returns YEAR-MONTH-DAY
        public static function calculateEndDate(int $duration,string $overrideStartDate = null) :array{
            (int)$duration = $duration/365;
            $month = date("m");
            $year = date("Y");
            $startDate = date('m/d/Y');
            if($overrideStartDate != null){
                $time_input = strtotime($overrideStartDate);
                $date_input = getDate($time_input);
                $d = date_create($overrideStartDate);
                $startDate = date_format($d, 'm/d/Y');
                $month = $date_input['mon'];
                $year = $date_input['year'];
            }
            if($month >= 1 && $month <=9){
                $endYear = $year+$duration;
                $expireDate = date_create("01-04-".$endYear);
                $msg = 'Since you\'re signing up before October 1st, your membership will end on April 1st of next year.';
                if($overrideStartDate != null){
                    $msg = 'Since your current membership ends on '.$startDate.', which is before October 1st, your renewed membership will end on April 1st of next year.';
                }
                return [
                                                //day-month-year
                    'date' => $expireDate->format('F j, Y'),
                    'startDate' => $expireDate->format('F j, Y'),
                    'sqlDate' => $expireDate->format('Y-m-d'),
                    'msg' => $msg
                ];
            }else{
                $endYear = $year+$duration;
                $expireDate = date_create("01-04-".$endYear);
                $msg = '';
                if($overrideStartDate != null){
                    $msg = '';
                }
                return [
                                                //day-month-year
                    'date' => $expireDate->format('F j, Y'),
                    'startDate' => $expireDate->format('F j, Y'),
                    'sqlDate' => $expireDate->format('Y-m-d'),
                    'msg' => $msg
                ];
            }

        }

        public function add() : int{
            try {
                $conn = SQLServices::makeCoreConnection();
                $smt = $conn->prepare("INSERT INTO `subscriptions` (`accountId`,`planId`,`name`, `price`, `duration`, `description`,`end`) VALUES (:accountId,:planId,:name,:price,:duration,:description,:end)");
                $smt->execute(array(
                    ":accountId" => $this->accountId,
                    ":planId" => $this->planId,
                    ":name" => $this->name,
                    ":price" => $this->price,
                    ":duration" => $this->duration,
                    ":description" => $this->description,
                    ":end" => $this->end
                ));
                return $conn->lastInsertId();
            } catch (Exception $e) {
                BaseClass::logError([
                    'message' => 'Failed to add Subscription',
                    'exception' => $e
                ]);
                return -1;
            }
        }

        public static function get(int $accountId):array | bool{
            try {
                $query = 'SELECT *,DATE_FORMAT(`end`, "%m/%d/%Y") AS endNiceDate,DATE_FORMAT(`start`, "%m/%d/%Y") AS startNiceDate, DATEDIFF(`end`,CURDATE() ) as daysUntilExpire FROM subscriptions WHERE accountId=? LIMIT 1';
                $statement = SQLServices::makeCoreConnection()->prepare($query);
                $statement->execute([$accountId]);
                return $statement->rowCount() > 0 ? $statement->fetch(PDO::FETCH_ASSOC) : false;
            } catch (Exception $e) {
                BaseClass::logError([
                    'message' => 'Failed to get Account Email',
                    'exception' => $e
                ]);
                return false;
            }
        }

        public static function renew(array $data):void{
            $subscriptionEndDate = Subscriptions::calculateEndDate($data['duration']);
            try{
                $smt = SQLServices::makeCoreConnection()->prepare("UPDATE `subscriptions` SET `start`=now(),`name`=:name,`price`=:price,`duration`=:duration,`description`=:description,`end`=:end WHERE `accountId`=:accountId LIMIT 1");
                $smt->execute([
                    ':name' => $data['name'],
                    ':price' => $data['price'],
                    ':duration' => $data['duration'],
                    ':description' => $data['description'],
                    ':end' => $data['end']['sqlDate'],
                    ':accountId' => $data['accountId']
                ]);
            }catch(Exception $e){
                BaseClass::logError([
                    'message' => 'Failed to update payment Scheduler',
                    'exception' => $e
                ]);
            }

        }

        public static function updateEndDate(int $accountId, string $endDate, string $reason, string $action):bool{
            $signedInAccountId = SessionManager::getAccountID();
            try{
                $account = new Account($accountId);
                $membershipData = self::get($account->accountId);
                $smt = SQLServices::makeCoreConnection()->prepare("UPDATE `subscriptions` SET `end`=:end, `notes`=:notes, `updatedBy`=:updatedBy WHERE `accountId`=:accountId LIMIT 1");
                $smt->execute([
                    ':end' => $endDate,
                    ':notes' => $reason,
                    ':updatedBy' => $signedInAccountId,
                    ':accountId' => $accountId
                ]);
                $account = new Account($accountId);
                $email = $account->email;
                $requestedNewEndDate = date_create($endDate);
                $newEndDate = date_format($requestedNewEndDate,"m/d/Y");
                if($action == 'cancel'){
                    $leftHeader = 'Your membership has been cancelled';
                    $intro = 'Effective immediately, your membership has been cancelled.';
                    $bodyText = [
                        'Reason for this change:<br>'.$reason
                    ];
                }else{
                    $leftHeader = 'Your membership duration has been updated.';
                    $intro = 'Hey '.$account->firstName.', we\'ve updated the expiration of membership.';
                    $bodyText = [
                        'Your membership original expiration date was on '.$membershipData['endNiceDate'].'. It is now scheduled to expire on '.$newEndDate.'.',
                        'Reason for this change:<br>'.$reason
                    ];
                }

                Mailer::sendSingleEmail(
                    '/generic.twig',
                    [
                        //TEMPLATE DATA
                        'firstName' => $account->firstName,
                        'fullName' => $account->fullName,
                        'headerLeft' => $leftHeader,
                        'headerRight' => $account->fullName.' | Account #'.$account->accountId,
                        'jumboTitle' => 'Changes to your membership',
                        'intro' => $intro,
                        'body' => implode('<br><br>',$bodyText),
                        'buttonLink' => '',
                        'buttonText' => '',
                        'signature' => 'Best Regards,<br>BUSINESS_NAME_HIDDEN'
                    ],
                    $account->firstName.' '.$account->lastName,
                    MailConfiguration::$MEMBERSHIP_UPDATE_SENDER_ADDRESS,
                    'Changes to your membership',
                    $email
                );

                return true;
            }catch(Exception $e){
                BaseClass::logError([
                    'message' => 'Failed to update payment Scheduler',
                    'exception' => $e
                ]);
                return false;
            }

        }



    }