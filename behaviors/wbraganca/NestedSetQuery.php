<?php
namespace anda\core\behaviors\wbraganca;
use yii\helpers\Url;

class NestedSetQuery extends \wbraganca\behaviors\NestedSetQuery
{
    public function dataMenu($root = 0, $level = null, $action = 'nav/view')
    {
        $data = array_values($this->prepareData2Menu($root, $level, $action));
        return $this->makeData2Menu($data);
    }

    private function prepareData2Menu($root = 0, $level = null, $action)
    {
        $res = [];
        if (is_object($root)) {
            $linkOptions = [];
            $linkTagOptions = implode(' ', $linkOptions);
            $res[$root->{$root->idAttribute}] = [
                'id' => $root->{$root->idAttribute},
                'label' => $root->{$root->titleAttribute},
                'url' => Url::to([$action,'id'=>$root->{$root->idAttribute}]),
                'linkOptions' => $linkTagOptions
            ];

            if ($level) {
                foreach ($root->children()->all() as $childRoot) {
                    $aux = $this->prepareData2Menu($childRoot, $level - 1, $action);

                    if (isset($res[$root->{$root->idAttribute}]['items']) && !empty($aux)) {
                        $res[$root->{$root->idAttribute}]['folder'] = true;
                        $res[$root->{$root->idAttribute}]['items'] += $aux;

                    } elseif(!empty($aux)) {
                        $res[$root->{$root->idAttribute}]['folder'] = true;
                        $res[$root->{$root->idAttribute}]['items'] = $aux;
                    }
                }
            } elseif (is_null($level)) {
                foreach ($root->children()->all() as $childRoot) {
                    $aux = $this->prepareData2Menu($childRoot, null, $action);
                    if (isset($res[$root->{$root->idAttribute}]['items']) && !empty($aux)) {
                        $res[$root->{$root->idAttribute}]['folder'] = true;
                        $res[$root->{$root->idAttribute}]['items'] += $aux;

                    } elseif(!empty($aux)) {
                        $res[$root->{$root->idAttribute}]['folder'] = true;
                        $res[$root->{$root->idAttribute}]['items'] = $aux;
                    }
                }
            }
        } elseif (is_scalar($root)) {
            if ($root == 0) {
                foreach ($this->roots()->all() as $rootItem) {
                    if ($level) {
                        $res += $this->prepareData2Menu($rootItem, $level - 1, $action);
                    } elseif (is_null($level)) {
                        $res += $this->prepareData2Menu($rootItem, null, $action);
                    }
                }
            } else {
                $modelClass = $this->owner->modelClass;
                $model = new $modelClass;
                $root = $modelClass::find()->andWhere([$model->idAttribute => $root])->one();
                if ($root) {
                    $res += $this->prepareData2Menu($root, $level, $action);
                }
                unset($model);
            }
        }
        return $res;
    }

    private function makeData2Menu(&$data)
    {
        $tree = [];
        foreach ($data as $key => &$item) {
            if (isset($item['items'])) {
                $item['items'] = array_values($item['items']);
                $tree[$key] = $this->makeData2Menu($item['items']);
            }
            $tree[$key] = $item;
        }
        return $tree;
    }

    public function KrSort()
    {
        $parents = $this->asArray()->all();
        $result = [];
        foreach ($parents as $parent){
            $result[] = $parent;
        }
        krsort($result);

        return $result;
    }
}