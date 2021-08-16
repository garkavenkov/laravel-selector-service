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
        
        $where = $this->getWhereClause($filterableFields, $parameters);

        $data = $model::with($relationship);
        
        if ($where) {
            $data = $data->whereRaw($where);
        }

        if ($random) {
            $data = $data->inRandomOrder()->limit($random);
        }

        if ($sortable) {
            foreach($sortable as $field => $order) {
                $data = $data->orderBy($field, $order);
            }
        }

        $data = $data->get($fields);
        
        return $data;  
    }


    private function getRelationship(&$parameters)
    {
        $relationship = [];

        if (array_key_exists('with', $parameters)) {
            $relationship = explode(',', $parameters['with']);
            unset($parameters['with']);
        }

        return $relationship;
    }

    private function getWhereClause($filterableFields, &$parameters) {
        $where  = '';

        foreach ($parameters as $field => $value) {

            if (array_key_exists($field, $filterableFields)) {
                
                // field=[x,y]  -----  BETWEEN x AND Y
                preg_match("/\[(.*)\]/", $value, $matches);

                if ($matches) {
                    $values = explode(',', $matches[1]);
                    
                    if (count($values) == 1) {
                        $condition = "{$field} between {$values[0]} and {$values[0]}";
                    } else if (count($values) == 2) {
                        $condition = "{$field} between {$values[0]} and {$values[1]}";
                    } else if (count($values) > 2) {
                        $condition = "{$field} between {$values[0]} and {$values[count($values) -1]}";
                    }

                } else {
                    // field=x,y  ---- field IN (x,y)
                    $values = explode(',', $value);

                    if (count($values) == 1) {
                        if ($filterableFields[$field] == 'string') {
                            $condition = "{$field} like '%{$values[0]}%'";
                        } else {
                            $condition = "{$field} = $values[0]";
                        }
                    } else {
	                if ($filterableFields[$field] == 'string') {
                            $condition = "{$field} in ('" . implode("', '", $values) . "')";      
                        } else {
                            $condition = "{$field} in ({$value})";
                        }
                    }
                }

                if ($where) {
                    $where = $where . ' and ' . $condition;
                } else {
                    $where = $where . $condition;
                }
            }           
        }
        return $where;
    }  
    
    private function getFields(&$parameters)
    {
        $fields = '*';

        if (isset($parameters['fields'])) {
            $fields = explode(',', $parameters['fields']);
            unset($parameters['fields']);
        }

        return $fields;
    }

    private function getSortableFields(&$parameters)
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

    private function getRandom(&$parameters)
    {
        $random = null;

        if (isset($parameters['get_random'])) {
            $random = $parameters['get_random'];
            unset($parameters['get_random']);
        }
        
        return $random;
    }
}
