<?php

    use JetBrains\PhpStorm\Pure;

    class Pagination {

        private int $currentPage = 1;
        private int $pageSize = 10;
        private ?int $accountId = -1;
        private ?array $fixedWhereValues = array();
        private string $query;
        private ?string $startRange = null;
        private ?string $endRange = null;
        private ?string $dateColumn = null;
        private ?string $category = null;
        private ?string $search = null;
        private ?string $sort = null;
        private array $columns;
        private bool $precisionSearch = false;

        public function __construct( $params ){
            $this->columns = $params['columns'];
            $this->query = $params['query'];
            if(isset($params['request']['currentPage'])){
                $this->currentPage = Filters::numberFilter($params['request']['currentPage']);
            }


            if(isset($params['request']['pageSize'])){
                $this->pageSize = Filters::numberFilter($params['request']['pageSize']);
            }

            if(isset($params['request']['startDate'])){
                $this->startRange = Filters::dateFilter($params['request']['startDate']);
            }


            if(isset($params['request']['endDate'])){
                $this->endRange = Filters::dateFilter($params['request']['endDate']);
            }

            if(isset($params['request']['dateColumn'])){
                $this->dateColumn = Filters::postClean($params['request']['dateColumn']);
                $this->dateColumn = str_replace("-",".",$this->dateColumn);
            }


            if(isset($params['request']['category'])){
                $this->category = Filters::postClean($params['request']['category']);
                $this->category = str_replace("-",".",$this->category);
            }


            if(isset($params['request']['search'])){
                $this->search = Filters::searchFiler($params['request']['search']);
            }


            if(isset($params['request']['sort'])){
                $this->sort = Filters::postClean($params['request']['sort']);
                $this->sort = str_replace("-",".",$this->sort);
            }


            if(isset($params['accountId'])){
                $this->accountId = Filters::numberFilter($params['accountId']);
            }

            if(isset($params['fixedWhereValues'])){
                $this->fixedWhereValues = $params['fixedWhereValues'];
            }
        }


        public function getSort() : string{
            $sortString = '';
            if($this->sort != null){
                $sortParams = explode(':',$this->sort);
                $i = 0;
                foreach ($sortParams as $key => $value){
                    if ($i % 2 == 0) {
                        if(in_array($value, $this->columns)) {
                            $sortString .= $value . ' ';
                        }else{
                            //Just exit. Action would be intentional manipulation
                            return false;
                        }
                    }else{
                        $sortString .= $value === 'ASC' || $value === 'DESC' ? $value : 'DESC';
                        if(($i+1) < count($sortParams)) {
                            $sortString .= ',';
                        }else{
                            $sortString .= ' ';
                        }
                    }
                    $i++;
                }
                return $sortString;
            }
            return false;
        }

        #[Pure] public static function findBestKeyMatch($data, $Singular = false) {
            $max = 0;
            $index = 0;
            foreach ($data['array'] as $key => $value) {
                $target = $Singular ? $value : $key;
                $comp = array_intersect(str_split(strtolower($data['term'])), str_split(strtolower($target)));
                if(count($comp) > $max){
                    $index = $value;
                    $max = count($comp);
                }
            }
            return $index;
        }

        private function SmartConvert(): bool {
            $category = strtolower($this->category);
            if(strtolower($category) === 'accountId'){
                if(is_numeric($this->search)) {
                    $this->precisionSearch = true;
                    $this->category = $this->findBestKeyMatch([
                        'array' => $this->columns,
                        'term' => $this->category
                    ],true);
                }
            }else if(strtolower($category) === 'status'){
                if(!is_numeric($this->search)) {
                    $this->precisionSearch = true;
                    $this->search = $this->findBestKeyMatch([
                        'array' => AccountDeclarations::getAccountState(false),
                        'term' => $this->search
                    ]);
                }
            }else if(in_array(strtolower($category),['email','phone'])){
                $this->search = Cryptography::encrypt($this->search,'e');
            }
            return true;
        }

        public function getRows(): array {
            $paging = array();
            $query = $this->query;
            if ($query != false) {
                $isInnerJoining = str_contains($query, ' JOIN ') == true;
                    try {

                        //Attempts to determine a possible datetime column for sorting
                        if($this->search != null){
                            if(in_array($this->category,$this->columns) || stripos($this->category,'id') > 0){
                                if($this->SmartConvert() != false) {
                                    $query .= strpos($this->category, '.') !== false ? " WHERE ( " . $this->category : " WHERE ( `" . $this->category . "`";
                                    if ($this->precisionSearch) {
                                        if (is_numeric($this->search)) {
                                            $query .= "=" . $this->search . " ";
                                        } else {
                                            $query .= "='" . $this->search . "' ";
                                        }
                                    } else {
                                        $query .= " LIKE '%" . $this->search . "%' ";
                                    }
                                    $query .= ") ";
                                }
                            }
                        }

                        if($this->startRange != null){
                            $query .=  $this->search != null ? " AND " : " WHERE ";
                            if(str_contains($this->dateColumn,'.')){
                                $query .= "(".$this->dateColumn." BETWEEN '".$this->startRange."' AND '".$this->endRange."') ";
                            }else{
                                $query .= "(`".$this->dateColumn."` BETWEEN '".$this->startRange."' AND '".$this->endRange."') ";
                            }
                        }

                        if($this->accountId != -1){
                            $query .= $this->startRange == null && $this->search == null ? ' WHERE ' : 'AND ';
                            if($isInnerJoining){
                                //Determines a unique column to filter accountId on
                                $column = $this->findBestKeyMatch([
                                    'array' => $this->columns,
                                    'term' => '.'
                                ],true);

                                $query .= '('.$column.'='.$this->accountId.') ';
                            }else{
                                $query .= '(accountId='.$this->accountId.') ';
                            }
                        }

                        $fixedWhereValuesLength =  count($this->fixedWhereValues);
                        if($fixedWhereValuesLength != 0){
                            $query .= $this->startRange == null && $this->search == null && $this->accountId == -1 ? ' WHERE ' : 'AND ';
                            $i = 0;
                            foreach ($this->fixedWhereValues as $fixedColumn => $value){
                                $query .= sprintf("%s='%s'",$fixedColumn,$value);
                                $i++;
                                if($i < $fixedWhereValuesLength)
                                    $query .= 'AND ';
                            }
                        }

                        if ($this->sort != null) {
                            $query .= " ORDER BY " . $this->getSort();
                        }else{
                            //Attempts to determine a possible datetime column for sorting
                            $dateColumn = $this->findBestKeyMatch([
                                'array' => $this->columns,
                                'term' => 'datepostedloggedcreated'
                            ],true);

                            $query .= " ORDER BY " . $dateColumn. " DESC";
                        }

                        $query .= " limit " . (($this->currentPage - 1) * $this->pageSize) . ',' . $this->pageSize;
                        $conn = SQLServices::makeCoreConnection();
                        $statement = $conn->prepare($query);
                        $statement->execute();
                        $result = $statement->fetchAll();
                        $pageSize = $statement->rowCount();
                        if($pageSize > 0) {
                            $paging = [
                                'currentPage' => $this->currentPage,
                                'pageSize' => $pageSize,
                                'totalRows' => $result[0]["totalRows"],
                                'totalPages' => round($result[0]["totalRows"] / $this->pageSize) > 0 ? round(Ceil($result[0]["totalRows"] / $this->pageSize)) : 1 ,
                                'rowStartAt' => (($this->currentPage - 1) * $this->pageSize + 1) == 0 ? 1 : (($this->currentPage - 1) * $this->pageSize + 1),
                                'rowEndAt' => $pageSize < $this->pageSize ? $result[0]["totalRows"] : ($this->currentPage * $pageSize),
                                'pageStartAt' => ($this->currentPage - 2) >= 1 ? $this->currentPage - 2 : 1,
                                'pageEndAt' => ($this->currentPage + 2 > round($result[0]["totalRows"] / $this->pageSize)) ? Ceil($result[0]["totalRows"] / $this->pageSize) : $this->currentPage + 2
                            ];
                        }
                        return [$result, $paging];
                } catch (Exception $e) {
                    BaseClass::logError([
                        'message' => 'Failed to get rows',
                        'exception' => $query
                    ]);
                    return array();
                }
            }else{
                BaseClass::logError([
                    'message' => 'Specified query name does not exist',
                    'exception' => $this->query
                ]);
                return array();
            }
        }

    }