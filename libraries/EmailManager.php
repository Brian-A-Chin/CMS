<?php


    class EmailManager {


        public function __construct() {

        }

        public static function getEmailCampaignDataById(int $id): array | bool{

            try {
                $query = 'SELECT * FROM emailCampaigns WHERE id=? LIMIT 1';
                $statement = SQLServices::makeCoreConnection()->prepare($query);
                $statement->execute([$id]);
                return $statement->rowCount() > 0 ? $statement->fetch(PDO::FETCH_ASSOC) : false;
            } catch (Exception $e) {
                BaseClass::logError([
                    'message' => 'Failed to get email Campaigns',
                    'exception' => $e
                ]);
                return false;
            }

        }

        public static function updateEmailCampaignData(int $id, int $start,int $interval): bool {
            try{
                SQLServices::makeCoreConnection()->prepare("UPDATE `emailCampaigns` SET `start`=?, `interval`=? WHERE `id`=?")->execute( [$start,$interval,$id]);
                return true;
            }catch(Exception $e){
                BaseClass::logError([
                    'message' => 'Failed to update email Campaigns',
                    'exception' => $e
                ]);
                return false;
            }
        }

        public static function updateSubscription(int $campaignId, int $accountId, int $subscribed): bool {
            try{
                SQLServices::makeCoreConnection()->prepare("UPDATE `emailList` SET `subscribed`=b? WHERE `campaignId`=? AND `accountId`=?")->execute( [$subscribed,$campaignId,$accountId]);
                return true;
            }catch(Exception $e){
                BaseClass::logError([
                    'message' => 'Failed to unsubscribe',
                    'exception' => $e
                ]);
                return false;
            }
        }

        public static function getSubscriptionDataById(int $campaignId, int $accountId): array | bool{

            try {
                $query = 'SELECT * FROM emailList WHERE campaignId=? AND accountId=? LIMIT 1';
                $statement = SQLServices::makeCoreConnection()->prepare($query);
                $statement->execute([$campaignId,$accountId]);
                return $statement->rowCount() > 0 ? $statement->fetch(PDO::FETCH_ASSOC) : false;
            } catch (Exception $e) {
                BaseClass::logError([
                    'message' => 'Failed to get email Campaigns',
                    'exception' => $e
                ]);
                return false;
            }

        }

        public static function sendTest($data): bool{

            return Mailer::sendSingleEmail(
                $data['template'],
                [
                    //TEMPLATE DATA
                    'firstName' => $data['firstName'],

                ],
                $data['firstName'].' '.$data['lastName'],
                MailConfiguration::$TEST_SENDER_ADDRESS,
                'Test Email',
                $data['email']
            );

        }

    }