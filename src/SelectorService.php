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
        $where = $this->getWhereClause($filterableFields, $parameters);

        $data = $model::with($relationship);
        
        if ($where) {
            $data = $data->whereRaw($where);
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
                            $condition = "{$field} like '%values[0]%'";
                        } else {
                            $condition = "{$field} = $values[0]";
                        }
                    } else {
                        $condition = "{$field} in ({$value})";
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
}