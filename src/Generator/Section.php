<?php

namespace Dingo\Api\Generator;

use Illuminate\Support\Collection;

abstract class Section
{
    /**
     * Get an annotation by its type.
     *
     * @param string $type
     *
     * @return mixed
     */
    protected function getAnnotationByType($type)
    {
        return array_first($this->annotations, function ($key, $annotation) use ($type) {
            $type = sprintf('Dingo\\Api\\Generator\\Annotation\\%s', $type);

            return $annotation instanceof $type;
        });
    }

    /**
     * Get a sections parameter annotations.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getParameters()
    {
        $parameters = new Collection;

        if ($annotation = $this->getAnnotationByType('Parameters')) {
            foreach ($annotation->value as $parameter) {
                $parameters[] = $parameter;
            }
        }

        return $parameters;
    }
}
