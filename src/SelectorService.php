<?php

namespace Garkavenkov\Laravel;

class SelectorService
{
    public function get($model, $parameters = [])
    {
        $parameters = request()->input();
        
        $filterableFields = $model::getFilterableFields();

        $relationship = $this->getRelationship($parameters);
        $fields = $this->getFields($parameters);
        $sortable = $this->getSortableFields($parameters);
        $random = $this->getRandom($parameters);
        $perPage = $this->getPerPage($parameters);
        $pageNumber = $this->getPageNumber($parameters);
        $scopes = $this->getScopes($parameters);
        
        $data = $model::with($relationship); 
                
        if ($scopes) {            
            foreach($scopes as $scope) {
                $data = $data->$scope();
            }            
        }

        $data = $this->getWhereClause($data, $filterableFields, $parameters);

        if ($random) {
            $data = $data->inRandomOrder()->limit($random);
        }

        if ($sortable) {
            foreach($sortable as $field => $order) {
                $data = $data->orderBy($field, $order);
            }
        }

        if ($perPage) {            
            $data = $data->paginate(perPage: $perPage, page: $pageNumber);            
            $data->appends(request()->query())->links();
        } else {
            $data = $data->get($fields);
        }
        
        return $data;  
    }

    /**
     * Get relationship(s) list
     *
     * @param array $parameters Request query parameters
     * @return array            Array of relatioship(s)
     */
    private function getRelationship(array &$parameters): array
    {
        $relationship = [];

        if (array_key_exists('with', $parameters)) {
            $relationship = explode(',', $parameters['with']);
            unset($parameters['with']);
        }

        return $relationship;
    }

        
    private function getWhereClause($builder, $filterableFields, &$parameters) {
        // $where  = '';
        
        if (array_key_exists('where', $parameters)) {
            // dd($parameters['where']);
            $conditions = explode(';',$parameters['where']);
            // dd($conditions);
            // foreach ($parameters as $field => $value) {
            foreach ($conditions as $condition) {
                // dd($condition);

                [$field, $value] = explode('=', $condition);

                // dd($field, $value);
                // Search in relationship
                if(strpos($field,'.') > 0) {

                    // dd('It\'s relationship');
                    $parts = explode('.', $field);
                    if (count($parts) > 2) {
                        // Deep nesting not ready yet.
                        // dd('Deep nesting search currently not supported');                        
                    } else {
                        $builder->whereHas($parts[0], function($q) use($parts, $value) {
                            return $q->where($parts[1], 'like', $value);
                        });
                    }
                    // dd($builder);

                } else {

                    if (array_key_exists($field, $filterableFields)) {
                    
                        // field=[x,y]  -----  BETWEEN x AND Y
                        preg_match("/\[(.*)\]/", $value, $matches);
        
                        if ($matches) {
                            $values = explode(',', $matches[1]);
                            
                            if (count($values) == 1) {
                                // $condition = "{$field} between {$values[0]} and {$values[0]}";
                                $builder->whereBetween($field, [$values[0], $values[0]]);
                            } else if (count($values) == 2) {
                                // $condition = "{$field} between {$values[0]} and {$values[1]}";
                                $builder->whereBetween($field, [$values[0], $values[1]]);
                            } else if (count($values) > 2) {
                                // $condition = "{$field} between {$values[0]} and {$values[count($values) -1]}";
                                $builder->whereBetween($field, [$values[0], $values[count($values) -1]]);
                            }
                            
        
                        } else {
                            // field=x,y  ---- field IN (x,y)
                            $values = explode(',', $value);
        
                            if (count($values) == 1) {
                                if ($filterableFields[$field] == 'string') {
                                    // $condition = "{$field} like '%{$values[0]}%'";
                                    $builder->where($field, 'like', "{$values[0]}");
                                } else {
                                    // $condition = "{$field} = $values[0]";
                                    $builder->where($field, $values[0]);
                                }
                            } else {
                                if ($filterableFields[$field] == 'string') {
                                    // $condition = "{$field} in ('" . implode("', '", $values) . "')";      
                                    // dd($condition, $values);
                                    // dd(implode("', '", $values));
                                    $builder->whereIn($field, $values);
                                } else {
                                    // $condition = "{$field} in ({$value})";
                                    $builder->whereIn($field, $values);
                                }
                            }
                        }
        
                        //if ($where) {
                        //    $where = $where . ' and ' . $condition;
                        //} else {
                        //    $where = $where . $condition;
                        //}
                    }           
                }
                
            }
        }
        // dd($builder);
        // return $where;
        return $builder;
    }  
    
    /**
     * Get list of returning fields in result set
     *
     * @param array $parameters Request query parameters
     * @return string|array     Array of fields, or '*' (all fields) as default
     */
    private function getFields(array &$parameters): string|array
    {
        $fields = '*';

        if (isset($parameters['fields'])) {
            $fields = explode(',', $parameters['fields']);
            unset($parameters['fields']);
        }

        return $fields;
    }

    /**
     * Get fields list for sorting
     *
     * @param array $parameters Request query parameters
     * @return array            Array of sorting fields
     */
    private function getSortableFields(array &$parameters): array
    {
        $sortable = [];

        if (isset($parameters['sort'])) {            
            $fields = explode(',', $parameters['sort']);
            unset($parameters['sort']);
            
            foreach($fields as $field) {

                preg_match("/^[-](.*)/", $field, $matches);

                if ($matches) {
			        $sortable[$matches[1]] = 'DESC';
		        } else {
		        	$sortable[$field] = 'ASC';
		        }
            }
        }

        return $sortable;
    }

    /**
     * Get per page records count for pagionation
     *
     * @param array $parameters Request query parameters
     * @return integer|null     Per page records
     */
    private function getPerPage(array &$parameters): ?int
    {
        $per_page = null;
        
        if (isset($parameters['per_page'])) {
            $per_page = $parameters['per_page'];
            unset($parameters['per_page']);
        }
        return $per_page;
    }

    /**
     * Get page number for pagination. If parameter is null, return 1 as default
     *
     * @param array $parameters Request query parameters
     * @return integer          Page number
     */
    private function getPageNumber(array &$parameters): int
    {
        $page_number = 1;
        
        if (isset($parameters['page'])) {
            $page_number = $parameters['page'];
            unset($parameters['page']);
        }
        return $page_number;
    }

    /**
     * Get random number for returning record set
     *
     * @param array $parameters Request query parameters
     * @return integer|null     Number of returning records
     */
    private function getRandom(array &$parameters): ?int
    {
        $random = null;

        if (isset($parameters['get_random'])) {
            $random = $parameters['get_random'];
            unset($parameters['get_random']);
        }
        
        return $random;
    }

    /**
     * Get scopes list
     *
     * @param array $parameters Request query paramters
     * @return array            Array of scope(s)
     */
    private function getScopes(array &$parameters): array
    {
        $scopes = [];

        if (isset($parameters['scope'])) {
            $scopes = explode(',', $parameters['scope']);                        
            unset($parameters['scope']);
        }

        return $scopes;
    }
}
