<?php

namespace Idimption\Entity;

class IdeaComment extends BaseEntity
{
    use ParentIdFieldTrait, UserIdFieldTrait, DateTimeFieldsTrait, IdeaIdFieldTrait;

    /**
     * @var string
     * @displayField
     */
    public $message = '';

    public function __construct()
    {
        parent::__construct('ideacomment');
    }
}
