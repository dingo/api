<?php

namespace Dingo\Api\Http;

use Illuminate\Http\Request as IlluminateRequest;

class Request extends IlluminateRequest
{
    /**
     * @param \Illuminate\Http\Request $old
     *
     * @return static
     */
    public static function createFromExisting(IlluminateRequest $old)
    {
        $new = new static(
            $old->query->all(), $old->request->all(), $old->attributes->all(),
            $old->cookies->all(), $old->files->all(), $old->server->all(), $old->content
        );

        if ($session = $old->getSession()) {
            $new->setSession($old->getSession());
        }

        $new->setRouteResolver($old->getRouteResolver());

        return $new;
    }
}
