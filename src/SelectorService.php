<?php

namespace Garkavenkov\Laravel;

class SelectorService
{
    public function get($model, $parameters = [])
    {
        $parameters = request()->input();

        $relationship = $this->getRelationship($parameters);

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

}