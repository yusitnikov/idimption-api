<?php

namespace Idimption;

use Idimption\Entity\AllEntities;
use Idimption\Entity\BaseEntity;
use Idimption\Entity\IdeaRelation;
use Idimption\Entity\IdeaStatus;
use Idimption\Entity\Relation;
use Idimption\Entity\Tag;
use Throwable;

class DbMock extends Db
{
    use SingletonMockTrait;

    const STATUS_NEEDS_REVIEW_ID = '100';
    const STATUS_NEEDS_REVIEW_NAME = 'Needs review';
    const STATUS_PLANNED_ID = '200';
    const STATUS_PLANNED_NAME = 'Planned';
    const STATUS_DONE_ID = '500';
    const STATUS_DONE_NAME = 'Done';

    const TAG_PROJECT_ID = '32';
    const TAG_PROJECT_NAME = 'project';
    const TAG_FEATURE_ID = '64';
    const TAG_FEATURE_NAME = 'feature';
    const TAG_ENHANCEMENT_ID = '96';
    const TAG_ENHANCEMENT_NAME = 'enhancement';

    const RELATION_RELATES_TO_ID = '128';
    const RELATION_RELATES_TO_NAME = 'relates to';
    const RELATION_IMPLEMENTS_ID = '192';
    const RELATION_IMPLEMENTS_NAME = 'implements';
    const RELATION_IMPLEMENTED_BY_ID = '193';
    const RELATION_IMPLEMENTED_BY_NAME = 'implemented by';

    public $autoIncrementId = 0;

    public $allEntities = [];

    protected function init()
    {
        $this->_log = new LoggerMock();

        foreach (AllEntities::getAllModels() as $model) {
            $this->allEntities[$model->getViewName()] = [];
        }

        $this->addMockRows([
            new IdeaStatus([
                'id' => self::STATUS_NEEDS_REVIEW_ID,
                'summary' => self::STATUS_NEEDS_REVIEW_NAME,
            ]),
            new IdeaStatus([
                'id' => self::STATUS_PLANNED_ID,
                'summary' => self::STATUS_PLANNED_NAME,
            ]),
            new IdeaStatus([
                'id' => self::STATUS_DONE_ID,
                'summary' => self::STATUS_DONE_NAME,
            ]),
            new Tag([
                'id' => self::TAG_PROJECT_ID,
                'summary' => self::TAG_PROJECT_NAME,
            ]),
            new Tag([
                'id' => self::TAG_FEATURE_ID,
                'summary' => self::TAG_FEATURE_NAME,
            ]),
            new Tag([
                'id' => self::TAG_ENHANCEMENT_ID,
                'summary' => self::TAG_ENHANCEMENT_NAME,
            ]),
            new Relation([
                'id' => self::RELATION_RELATES_TO_ID,
                'oppositeId' => self::RELATION_RELATES_TO_ID,
                'isDirect' => true,
                'summary' => self::RELATION_RELATES_TO_NAME,
            ]),
            new Relation([
                'id' => self::RELATION_IMPLEMENTS_ID,
                'oppositeId' => self::RELATION_IMPLEMENTED_BY_ID,
                'isDirect' => true,
                'summary' => self::RELATION_IMPLEMENTS_NAME,
            ]),
            new Relation([
                'id' => self::RELATION_IMPLEMENTED_BY_ID,
                'oppositeId' => self::RELATION_IMPLEMENTS_ID,
                'isDirect' => false,
                'summary' => self::RELATION_IMPLEMENTED_BY_NAME,
            ]),
        ]);
    }

    public function dbLog($type, $data)
    {
    }

    public function selectAll($tableName)
    {
        return $this->allEntities[$tableName];
    }

    public function insertRow($tableName, $data, $log = true)
    {
        if ($tableName === 'idearelation') {
            $tableName = 'idearelationfull';
        }

        $this->_insertedId = ++$this->autoIncrementId;
        $data['id'] = (string)$this->_insertedId;
        $this->allEntities[$tableName][] = $data;

        if ($tableName === 'idearelationfull') {
            $row = new IdeaRelation($data);
            $this->allEntities[$tableName][] = $row->getOpposite()->toArray(true);
        }
    }

    public function updateRow($tableName, $id, $data, $log = true)
    {
        if ($tableName === 'idearelation') {
            $tableName = 'idearelationfull';
        }

        foreach ($this->allEntities[$tableName] as &$rowRef) {
            if ((string)$rowRef['id'] === (string)$id) {
                $rowRef = array_merge($rowRef, $data);
                break;
            }
        }
    }

    public function deleteRow($tableName, $id, $log = true)
    {
        if ($tableName === 'idearelation') {
            $tableName = 'idearelationfull';
        }

        $this->allEntities[$tableName] = array_values(array_filter($this->allEntities[$tableName], function($row) use($id) {
            $rowId = (string)$row['id'];
            $id = (string)$id;
            return $rowId !== $id && $rowId !== '-' . $id;
        }));
    }

    public function transaction($callback)
    {
        $prevState = $this->allEntities;
        try {
            call_user_func($callback);
        } catch (Throwable $exception) {
            $this->allEntities = $prevState;
            throw $exception;
        }
    }

    /**
     * @param BaseEntity[] $rows
     */
    public function addMockRows($rows)
    {
        foreach ($rows as $row) {
            $this->allEntities[$row->getViewName()][] = $row->toArray(true);
        }
    }
}
