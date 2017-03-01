<?php
namespace anda\core\behaviors\wbraganca;

class NestedSetBehavior extends \wbraganca\behaviors\NestedSetBehavior
{


    /**
     * Gets parent of node.
     * @return ActiveQuery.
     */
//    public function parent()
//    {
//        $query = $this->owner->find();
//        $db = $this->owner->getDb();
//        $query->andWhere($db->quoteColumnName($this->leftAttribute) . '<'
//            . $this->owner->getAttribute($this->leftAttribute));
//        $query->andWhere($db->quoteColumnName($this->rightAttribute) . '>'
//            . $this->owner->getAttribute($this->rightAttribute));
//        $query->addOrderBy($db->quoteColumnName($this->rightAttribute));
//
//        if ($this->hasManyRoots) {
//            $query->andWhere(
//                $db->quoteColumnName($this->rootAttribute) . '=:' . $this->rootAttribute,
//                [':' . $this->rootAttribute => $this->owner->getAttribute($this->rootAttribute)]
//            );
//        }
//
//        return $query;
//    }
}