<?php
 /**
 *
 * NOTICE OF LICENSE
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * Classe responsável para criação de query's da Base de dados MySQL, onde terá metodos para facilitar chamadas de dados.
 * @author  Gois Neto - goisneto@gmail.com.br
 * @version 1.0
 * @license   http://www.apache.org/licenses/LICENSE-2.0
 */
class MySQLField{
	use ClassPack, ClassUtil;
	private $field;
	private $label;
	private $value;
	private $from;
	public function __toString(){ return json_encode($this->__debugInfo()); }
    public function __debugInfo(){ return ['field' => $this->field, 'label' => $this->label, 'value' => $this->value]; }
	public function __clone(){
		return $this->CloneObject(function($var, $val){
			if(is_object($val)) return NULL;
			return $val;
		});
	}
	public function __construct(string $field, string $value = '', string $label = NULL, MySQLTable $from = NULL){
		$this->field = $field;
		if(is_null($label))
			$this->label = $this->field;
		else
			$this->label = $label;
		$this->value = $value;
		$this->from = $from;
	}
	public function setFrom(MySQLTable $e){ $this->from = $e; }
	public function getName($withtb = true, $withlabel = true){
		$rt = '';
		if(is_string($this->getField())){
			$rt .= $this->getField($withtb);
			if($withlabel){
				if(is_string($this->getLabel($withtb)))
					$rt .= ' AS \''.$this->getLabel($withtb).'\'';
				else
					$rt .= ' AS \''.$this->getField($withtb).'\'';
			}
				
		}
		return $rt;
	}
	public function getLabel($withtb = true){ $rt = ($withtb?$this->getTable().'.':''); if(isset($this->label) && $this->label != '')  $rt .= $this->label; else $rt .= $this->getField(); return $rt; }
	public function getField($withtb = true){ if(isset($this->field)) return ($withtb?$this->getTable().'.':'').$this->field; }
	public function getValue(){ if(isset($this->value)) return $this->value; }
	public function getTable(){ if(is_a($from = $this->getFrom(), 'MySQLTable') && !is_null($from->label)) return $from->label; else return ''; }
	public function getFrom(){ if(isset($this->from)) return $this->from; }
}
class MySQLTable implements ArrayAccess{
	use ClassPack, ClassUtil;
	private $data = [];
	private $table;
	private $label;
	public function __toString(){ return json_encode($this->__debugInfo()); }
    public function __debugInfo(){ return ['table' => $this->table, 'label' => $this->label, 'data' => $this->data]; }
	public function __clone(){
		return $this->CloneObject(function($var, $val){
			if($var == 'data'){
				$rt = [];
				$this->each(function($k, &$v, $d) use(&$rt){ $v = (is_object($v)?clone $v:$v); if(is_object($v)) $v->setFrom($this); $rt[] = $v; });
				return $rt;
			}
			if(is_object($val)) return clone $val;
			return $val;
		});
	}
	public function __construct($table = NULL, $label = NULL){
		if(!is_null($table)) $this->setTable($table);
		if(!is_null($label)) $this->setLabel($label);
	}
	public function __get($n){
		switch($n){
			case 'table': return $this->getTable();
			case 'label': return $this->getLabel();
			case 'name': return $this->getName();
		}
	}
	public function setTable($table){
		if(is_string($table)) $this->table = $table;
		if(is_array($table) && count($table) > 0) $this->table = $table[0];
	}
	public function setLabel($label){
		if(is_string($label)) $this->label = $label;
		if(is_array($label) && count($label) > 0) $this->label = $label[0];
	}
	public function getName(){ if(!is_null($this->getTable())) return $this->getTable().' AS '.$this->getLabel(); }
	public function getTable(){ if(isset($this->table)) return $this->table; }
	public function getLabel(){ if(isset($this->label) && $this->label != '') return $this->label; else return $this->table; }
	public function replaceVars($str, bool $invertTableLabel = false){
		if($invertTableLabel){
			$str_ = str_replace($this->getLabel(), $this->getTable(), $str);
			if($str_ != $str) $str = $str_;
			else  $str = str_replace($this->getTable(), $this->getLabel(), $str);
		}
		$str_ = str_replace('_QUOTE_', '\'', str_replace('_AS_', ' AS ', str_replace('_DOT_', '.', str_replace(':m', '', $str))));
		if($str_ != $str) $str = $str_;
		else  $str = str_replace('\'', '_QUOTE_', str_replace(' AS ', '_AS_', str_replace('.', '_DOT_', ':m'.$str)));;
		return $str;
		}
	public function getFields($assoc = true, $values = false, $withtb = true, $PDOvar = false, $withlabel = true, $inVertLabelTable = false){
		$rt = [];
		if($assoc) foreach($this->data as $val) $rt[($PDOvar?$this->replaceVars($val->getName($withtb, $withlabel), !$withlabel && !$inVertLabelTable):$val->getName($withtb, $withlabel))] = $val->getValue();
		else if($values) foreach($this->data as $val) $rt[] = $val->getValue();
		else foreach($this->data as $val) $rt[] = ($PDOvar?$this->replaceVars($val->getName($withtb, $withlabel), !$withlabel && !$inVertLabelTable):$val->getName($withtb, $withlabel));
		return $rt;
	}
	public function length(){ return count($this->data); }
	public function each($cb){
		if(is_callable($cb)) foreach($this->data as $key => $val) $cb($key, $val, $this->data);
	}
	public function offsetExists ($offset){
		if(is_int($offset))
			return !is_null($this->data[$offset]);
		if(is_string($offset))
			foreach($this->data as $val)
				if($val->getField() == $offset || $val->getName() == $offset)
					return true;
		return false;
	}
	public function offsetGet ($offset){
		if(is_int($offset))
			return $this->data[$offset];
		if(is_string($offset))
			foreach($this->data as $key => $val)
				if($val->getField() == $offset || $val->getName() == $offset)
					return $this->data[$key];
	}
	public function offsetSet ($offset, $value){
		if(is_a($value, 'MySQLTable')){
			$value->each(function($k, &$v, $d){ $this->offsetSet(NULL, $v); });
			return;
		}
		if(is_string($value)) $value = [$value, '', NULL];
		if(is_array($value) && $this->isAssoc($value)){
			foreach($value as $key => $val){
				if($key != '')
					if(intval($key.'1') >= 1)
						$this->offsetSet(NULL, $val);
					else if(is_array($val) && $this->isAssoc($val)){
						if(!isset($val['value'])) $val['value'] = '';
						$this->offsetSet(NULL, [$key, $val['value']]);
					}
					else if(is_a($val, 'DateTime'))
						$this->offsetSet(NULL, [$key, $val->format('Y-m-d H:i:s')]);
					else
						try{$this->offsetSet(NULL, [$key, Encoding::fixUTF8((string)$val)]);}catch(Exeption $e){ throw $e; }
			}
			return;
		}
		if(is_array($value) && count($value) < 2) $value = [$value[0], '', NULL];
		if(is_array($value) && count($value) < 3) $value = [$value[0], $value[1], NULL];
		if(is_array($value)) $value = new MySQLField($value[0], $value[1], $value[2], $this);
		if(is_a($value, 'MySQLField') && $value->getTable() == $this->getLabel()){
			if(is_null($offset)) $this->data[] = $value;
			else if(is_int($offset)){
				if($offset < count($data)) $this->data[$offset] = $value;
				else $this->data[] = $value;
			}
			else if(is_string($offset)){
				$this->offsetUnset($offset);
				$this->data[] = $value;
			}
		}
	}
	public function offsetUnset ($offset){
		if(!$this->offsetExists($offset)) return false;
		$buff = [];
		if(is_int($offset))
			foreach($this->data as $key => $val)
				if($val !== $offset) $buff[] = $val;
		unset($this->data);
		$this->data = $buff;
		if(is_string($offset))
			foreach($this->data as $key => $val)
				if($val->getField() == $offset || $val->getName() == $offset)
					return $this->offsetUnset($key);

	}
}
class MySQLTableLink{
	use ClassPack, ClassUtil;
	private $data = [];
	public function __toString(){ return json_encode($this->__debugInfo()); }
    public function __debugInfo(){ return ['data' => $this->data]; }
	public function __clone(){
		return $this->CloneObject(function($var, $val){
			if($var == 'data'){
				$rt = [];
				$this->each(function($k, &$v, $d) use(&$rt){ $rt[] = (is_object($v)?clone $v:$v); });
				return $rt;
			}
			if(is_object($val)) return clone $val;
			return $val;
		});
	}
	public function __construct(array $links = NULL){
		if(!is_null($links)) $this->data = $links;
	}
	public function push($obj){ if(is_object($obj)) return $this->data[] = $obj; }
	public function each($cb){
		if(is_callable($cb))
			foreach($this->data as $key => $val)
				if(($rt = $cb($key, $val, $this->data)) !== NULL) return $rt;
	}
	public function __call($f, $args){
		foreach($this->data as $val)
			if(is_object($val) && method_exists($val, $f))
				if(($rt = call_user_func_array(array($val, $f), $args)) !== NULL)
					return $rt;
	}
}
class MySQLTableList implements ArrayAccess{
	use ClassPack, ClassUtil;
	private $data = [];
	private $linkeds;
	public function __toString(){ return json_encode($this->__debugInfo()); }
    public function __debugInfo(){ return ['linkeds' => $this->linkeds, 'data' => $this->data]; }
	public function __clone(){
		return $this->CloneObject(function($var, $val){
			if($var == 'data'){
				$rt = [];
				$this->each(function($k, &$v, $d) use(&$rt){ $rt[] = (is_object($v)?clone $v:$v); });
				return $rt;
			}
			if(is_object($val)) return clone $val;
			return $val;
		});
	}
	public function __construct(){
		$this->linkeds = new MySQLTableLink();
	}
	public function getFields($assoc = true, $values = false, $withtb = true, $PDOvar = false, $withlabel = true, $inVertLabelTable = false){
		$rt = [];
		foreach($this->data as $val) $rt = array_merge($rt, $val->getFields($assoc, $values, $withtb, $PDOvar, $withlabel, $inVertLabelTable));
		$this->linkeds->each(function($k, $v, $d) use (&$rt, $assoc, $values, $withtb, $PDOvar, $withlabel, $inVertLabelTable){
			$rt = array_merge($rt, $v->getFields($assoc, $values, $withtb, $PDOvar, $withlabel, $inVertLabelTable));
		});
		return $rt;
	}
	public function length($all = true){
		$rt = 0;
		foreach($this->data as $val) $rt += $val->length();
		if($all) $this->linkeds->each(function($k, $v, $d) use (&$rt){
			$rt += $v->length();
		});
		return $rt;
	}
	public function each($cb){
		if(is_callable($cb))
			foreach($this->data as $key => &$val)
				if(($rt = $cb($key, $val, $this->data)) !== NULL) return $rt;
	}
	public function offsetExists ($offset){
		if(is_int($offset))
			return isset($this->data[$offset]) && !is_null($this->data[$offset]);
		if(is_string($offset)){
			foreach($this->data as $val)
				if($val->getTable() == $offset)
					return true;
			return $linkeds->each(function($k, $v, $d) use ($offset){
				if($v->getTable() == $offset)
					return true;
			});
		}
		return false;
	}
	public function offsetGet ($offset){
		if(is_int($offset))
			return $this->data[$offset];
		if(is_string($offset)){
			foreach($this->data as $val)
				if($val->getTable() == $offset)
					return $val;
			return $linkeds->each(function($k, $v, $d) use ($offset){
				if($v->getTable() == $offset)
					return $v;
			});
		}
	}
	public function offsetSet ($offset, $value){
		if(is_a($value, 'MySQLTableList')) return $this->linkeds->push($value);
		if(is_array($value) || is_string($value)) $value = new MySQLTable($value);
		if(is_a($value, 'MySQLTable')){
			if(is_null($offset)) $this->data[] = $value;
			else if(is_int($offset))
				if($offset < count($this->data)) $this->data[$offset] = $value;
				else $this->data[] = $value;
			else if(is_string($offset)){
				if($this->offsetExists($offset)){
					$this->offsetUnset($offset);
				}
				$this->data[] = $value;
			}
		}
	}
	public function offsetUnset ($offset){
		if(!$this->offsetExists($offset)) return false;
		$buff = [];
		$rt;
		if(is_int($offset)){
			foreach($this->data as $key => $val)
				if($val !== $offset) $buff[] = $val;
				else $rt = $val;
			unset($this->data);
			$this->data = $buff;
			return $rt;
		}
		else if(is_string($offset)){
			foreach($this->data as $key => $val)
				if($val->getTable() === $offset)
					return $this->offsetUnset($key);
			return $linkeds->each(function($k, $v, $d) use ($offset, $val){
				if($v->getTable() == $offset)
					return $v->offsetUnset($k);
			});
		}

	}
}
class MySQLJoin {
	use ClassPack, ClassUtil;
	public $context;
	public $relation;
	public $operator;
	public $type;
	public function __toString(){ return json_encode($this->__debugInfo()); }
    public function __debugInfo(){ return ['context' => $this->context, 'relation' => $this->relation, 'operator' => $this->operator, 'type' => $this->type]; }
	public function __clone(){
		return $this->CloneObject(function($var, $val){
			if(is_object($val)) return clone $val;
			return $val;
		});
	}
	function __construct(MySQLUtil $context, $relation, string $operator, string $type, string $label = NULL){
		if(is_string($relation)) $relation = array($relation);
		if(count($relation) < 2) $relation[] = $relation[0];
		if(!is_array($relation)) throw new Exception('Parametro relation deve ser do tipo Array ou String.');
		$context = clone $context;
		if(!is_null($label) && $label != '')
			$context->setLabel($label);
		$this->context = $context;
		$this->relation = $relation;
		$this->operator = $operator;
		$this->type = $type;
	}
}
class MySQLUtil {
	use ClassPack, ClassUtil;
	public $queryBuff;
	public $tables;
	private $objJoin;
	private $queryType;
	private $where;
	private $id;
	private $limit;
	private $offset;
	private $orderby;
	private $result;
	public function __toString(){ return json_encode($this->__debugInfo()); }
    public function __debugInfo(){ return ['query' => $this->getQuery(), 'result' => $this->result, 'error' => $this->errorInfo]; }
	public function __construct($_usuario, $_senha, $_host, $_dbname, $table = ''){
		$this->clear();
		$this->__constructor_parameters__ = func_get_args();
		$this->setTable($table);
	}
	public function __clone(){
		return $this->CloneObject(function($var, $val){
			if(is_object($val)) return clone $val;
			return $val;
		});
	}
	public function connect($cb){
		$params = $this->__constructor_parameters__;
		call_user_func($func_db = function($obj, $i = 0, $exception = NULL) use (&$func_db, &$pdo){
			if(is_array($obj)){
				$bkpObj = $obj;
				foreach($bkpObj as $key => $val){
					if(is_array($val)){
						if(count($val) > $i){
							$obj[$key] = $val[$i];
						}
						else {
							throw is_null($exception)?(new Exception('Erro de Banco de Dados não tratado ou sem Exception de erro.')):$exception;
						}
					}
				}
				$j = 0;
				$user = $obj[$j++];
				$pass = $obj[$j++];
				$dns = 'mysql:host='.$obj[$j++].';dbname='.$obj[$j++];
				$pdo = new PDO($dns, $user, $pass, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
			}
		}, $params);
		if(is_callable($cb)) $cb($pdo);
		$pdo = null;
		return $this;
	}
	public function clear(){
		$this->queryBuff = '';
		$this->tables = new MySQLTableList();
		$this->tables[] = new MySQLTable();
		$this->objJoin = array();
		$this->where = array();
		$this->setSelect();
	}
	public function __get($n){
		switch($n){
			case 'table': 
			case 'label': 
			case 'name': return $this->tables[0]->$n;
			case 'fields': return $this->tables[0];
		}
	}
	public function setSelect(){
		$this->queryType = 'SELECT';
		return $this;
		}
	public function setInsert(){
		$this->queryType = 'INSERT INTO';
		return $this;
		}
	public function setUpdate(){
		$this->queryType = 'UPDATE';
		return $this;
		}
	public function setDelete(){
		$this->queryType = 'DELETE';
		return $this;
		}
	public function setID($id){
		$this->id = $id;
		return $this;
		}
	public function setLimit(int $limit, int $offset = NULL){
		$this->limit = $limit;
		if(!is_null($offset)) $this->setOffset($offset);
		return $this;
		}
	public function setOffset(int $offset){
		$this->offset = $offset;
		return $this;
		}
	public function setWhere($var, $operator = '=', $var2, $logical = ''){
		if(!in_array(array($var, $operator, $var2, $logical), $this->where))
			array_push($this->where, array($var, $operator, $var2, $logical));
		return $this;
		}
	public function setTable($table_name){
			$this->fields->setTable($table_name);
			return $this;
		}
	public function setLabel($label_name){
			$this->fields->setLabel($label_name);
			return $this;
		}
	public function fullJoin($context, $relation, $operator = '=', $label = NULL){
		$this->join($context, $relation, $operator, 'FULL OUTER', $label);
		return $this;
		}
	public function rightJoin($context, $relation, $operator = '=', $label = NULL){
		$this->join($context, $relation, $operator, 'RIGHT', $label);
		return $this;
		}
	public function leftJoin($context, $relation, $operator = '=', $label = NULL){
		$this->join($context, $relation, $operator, 'LEFT', $label);
		return $this;
		}
	public function innerJoin($context, $relation, $operator = '=', $label = NULL){
		$this->join($context, $relation, $operator, 'INNER', $label);
		return $this;
		}
	public function addOrderBy($field, $ord = 'ASC'){
		if($field != '') $this->orderby[$field] = strtoupper($ord);
		return $this;
	}
	public function removeOrderBy($field){
		if($field != '') unset($this->orderby[$field]);
		return $this;
	}
	public function addArrayFields($fields){
		if($this->isAssoc($fields)) $this->fields[] = $fields;
		else foreach($fields as $key => $val){
			$this->addField($val);
		}
		return $this;
	}
	public function addField($field, $value = '', $label = NULL){
		if(is_string($field)){
			$field = [$field];
			$field[] = $value;
			$field[] = $label;
			$this->fields[] = $field;
		}
		return $this;
	}
	public function removeField($field){
		unset($this->fields[$field]);
		return $this;
		}
    public function getPDOQueryParams(&$query = NULL, &$params = NULL){
        $query = str_replace("\n", "", str_replace(" ;", ";", str_replace("; ", ";", $this->getQueryBuff())));
		$invertVars = in_array($this->queryType, ['INSERT INTO', 'UPDATE']);
        $params = $this->tables->getFields(true, true, true, true, !$invertVars, true);
        foreach($params as $key => &$obj){
            if(!is_bool($pos = strpos($obj, '(')) && substr($obj, -1) == ')'){
                if(!is_bool($posEscape = strpos($obj, '\\')) && $posEscape+1 == $pos){
                    $obj = $this->t_unescape($obj);
                    continue;
                }
                $command = substr($obj, 0, $pos);
                $obj = substr($obj, $pos+1, strlen($obj)-($pos+2));
                $query = str_replace($key, $command.'('.(($obj!='')?$key:'').')', $query);
                if(intval($key.'1') > 0) array_splice($params, $key, 1);
                else unset($params[$key]);
            }
        }
        unset($obj);
        foreach($this->where as $obj){
            if(!is_bool($pos = strpos($obj[0], '('))){
                $command = substr($obj[0], 0, $pos);
                $obj[0] = substr($obj[0], $pos+1, strlen($obj[0])-($pos+2));
            }
            if(!is_bool($pos = strpos($obj[2], '('))){
                $command = substr($obj[2], 0, $pos);
                $obj[2] = substr($obj[2], $pos+1, strlen($obj[2])-($pos+2));
                $query = str_replace(':where_'.$obj[0], $command.'('.(($obj[2]!='')?':where_'.$obj[0]:'').')', $query);
            }
            $params[':where_'.$obj[0]] = $obj[2];
        }
        $params = array_filter($params, function($k) use($query){ return strpos($query, $k)!==false; }, ARRAY_FILTER_USE_KEY);
        return array($params, $query);
    }
    public function getQueryBuff(){
		$this->makeQuery();
        return $this->queryBuff;
    }
	public function getQuery(){
        $this->getPDOQueryParams($query, $params);
        foreach($params as $key => $val){
            $query = str_replace($key, "'$val'", $query);
        }
		return $query;
		}
	public function runQuery(string $property = '', array $_params = array(), &$return = NULL){
		$this->connect(function($dbh) use(&$id, $property, $_params, &$return){
			try{
				$dbh->beginTransaction();
				$this->getPDOQueryParams($query, $params);
				$sth = $dbh->prepare($query);
				$PDOStatement = function(string $property = '', array $params = array()) use(&$sth){
					if(isset($sth))
						if($property != '' && property_exists($sth, $property))
							return $sth->$property;
						else if(method_exists($sth, $property))
							return call_user_func_array([$sth, $property], $params);
						else return $sth;
				};
				$this->result = $sth->execute($params);
				$this->errorInfo = $PDOStatement('errorInfo');
				if($property == 'errorInfo') $return = $this->errorInfo;
				else $return = $PDOStatement($property, $_params);
				if($this->queryType == 'SELECT')
					$this->result = $sth->fetchAll();
				$id = $dbh->lastInsertId();
				$dbh->commit();
			} catch(Exception $e){
				$dbh->rollBack();
				$sth = null;
				$dbh = null;
				throw new Exception('Erro na Preparação da Query, dados recuperados e não modificados.'.(isset($sth)?$sth:$dbh)->errorInfo());
			}
			$sth = null;
			$dbh = null;
		});
        if($this->queryType == 'INSERT INTO')
            return $id;
        else
		    return $this->result;
	}
	public function runResult($cb, string $property = '', array $_params = array(), &$return = NULL){
		if(!isset($this->result)) $this->runQuery($property, $_params, $return);
		if(!is_callable($cb)) throw new Exception('Callback no primeiro parametro deve ser callable(function(){}).');
		else if(!($this->result instanceof Traversable) && !is_array($this->result)) throw new Exception('Query retornou valor falso(bool(false)).');
		else{
			if(isset($this->id)){
				$order = [];
			}
			foreach($this->result as $row){
				if(isset($order)){
					if(isset($row[$id = $this->id]) || isset($row[$id = $this->label.'.'.$this->id])) $id = $row[$id];
					if(!isset($order[$id])) $order[$id] = $row;
				}
				foreach($row as $key => $val){
					if(isset($order) && isset($order[$id])) $act = &$order[$id];
					else $act = &$row;
					if(!is_bool($pos = strpos($key, $this->label.'.'))){
						if(!isset($row[$key_ = substr($key, strlen($this->label.'.'))])){
							if(isset($act[$key_])){
								if(!is_array($act[$key])) $act[$key] = [$act[$key]];
								$act[$key_][] = &$row[$key];
							}
							else $act[$key_] = &$row[$key];
						}
					}else if(!is_bool($pos = strpos($key, '.')))
						if(!isset($row[$key_ = substr($key, $pos+1)])){
							if(isset($act[$key_])){
								if(!is_array($act[$key_])) $act[$key_] = [$act[$key_]];
								$act[$key_][] = &$row[$key];
							}
							else $act[$key_] = &$row[$key];
						}
				}
				if(!isset($order) && !is_null($rt = $cb($act, $this->result, $this))) return $rt;
			}
			if(isset($order))
				foreach($order as $id => $val)
					if(!is_null($rt = $cb($order[$id], $this->result, $this)))
								return $rt;
		}
		return $this;
	}
	private function fieldFromId($table, $id, $field){
		$uid = uniqid();
		$this->appendQueryBuff("SET @TB{$uid} = {$table}; SET @ID{$uid} = {$id}; SET @PK{$uid} = (SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS AS TC INNER JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE AS KU ON TC.CONSTRAINT_TYPE = 'PRIMARY KEY' AND KU.table_name=TC.table_name AND TC.CONSTRAINT_NAME = KU.CONSTRAINT_NAME AND TC.table_name = @TB{$uid} ORDER BY KU.TABLE_NAME, KU.ORDINAL_POSITION); SET @SQL{$uid} = CONCAT('SET @RESULT{$uid} = (SELECT ', CONCAT(@PK{$uid}, CONCAT(' FROM ', CONCAT(@TB{$uid}, CONCAT(' WHERE  ', CONCAT(@PK{$uid}, CONCAT(' = ', CONCAT(@ID{$uid}, ');')))))))); PREPARE stmt{$uid} FROM @SQL{$uid}; EXECUTE stmt{$uid}; DEALLOCATE PREPARE stmt{$uid};", true);
		return "@RESULT".$uid;
	}
	private function makeQuery(){
			$this->queryBuff = '';
			$buffJoin = array();
			$forRm = array();
			switch($this->queryType){
				case 'DELETE':
				$this->appendQueryBuff($this->queryType);
				$this->appendQueryBuff('FROM '.$this->table);
				$this->appendQueryBuff($this->makeJoin());
				$this->makeWhere();
				break;
				case 'UPDATE':
				$reference = array();
				$this->loopJoin(function($obj) use(&$reference){
					$ctx = $obj->context;
					$ctx->setUpdate();
					$this->appendQueryBuff($ctx->getQueryBuff(), true);
					$uid = uniqid();
					$reference[] = array($obj->relation[1], $this->fieldFromId("'".$obj->context->table."'", '(LAST_INSERT_ID())', $obj->relation[0]));
				});
				$this->appendQueryBuff($this->queryType);
				$this->appendQueryBuff($this->table);
				$this->appendQueryBuff(' SET');
				$fields = $this->fields->getFields(true, true, true, true, false, true);
				$buff = '';
				foreach($reference as $field => $value) $buff .= $value[0].' = '.$value[1].', ';
				foreach($fields as $field => $value) $buff .= $this->fields->replaceVars($field, true).' = '.$field.', ';
				$this->appendQueryBuff(substr($buff, 0, -2));
				$this->makeWhere();
				break;
				case 'SELECT':
				$this->makeSelect();
				$this->appendQueryBuff($this->makeJoin());
				$this->makeWhere();
				if(count($this->orderby) > 0){
					$sorts = '';
					foreach($this->orderby as $key => $val)
						$sorts .= $key.' '.$val.',';
					$sorts = substr($sorts, 0, strlen($sorts) -1);
					$this->appendQueryBuff('ORDER BY '.$sorts);
				}
				if(isset($this->limit)) $this->appendQueryBuff('LIMIT '.$this->limit);
				if(isset($this->offset)) $this->appendQueryBuff('OFFSET '.$this->offset);
				break;
				case 'INSERT INTO':
				$reference = array();
				$this->loopJoin(function($obj) use(&$reference){
					$ctx = $obj->context;
					$ctx->setInsert();
					$this->appendQueryBuff($ctx->getQueryBuff(), true);
					$uid = uniqid();
					$reference[] = array($obj->relation[1], $this->fieldFromId("'".$obj->context->table."'", '(LAST_INSERT_ID())', $obj->relation[0]));
				});
				$this->makeInsert($reference);
				break;
			}
		}
	private function makeInsert(array $reference = NULL){
			$this->appendQueryBuff($this->queryType);
			$this->appendQueryBuff($this->table);
			$buff = '';
			$buff2 = '';
			if(!is_null($reference))
				foreach($reference as $field => $value){
					$buff .= $value[0].',';
					$buff2 .= $value[1].',';
				}
            $fields = $this->fields->getFields(true, true, true, true, false, true);
			foreach($fields as $field => $value){
				$exp = explode("_DOT_" ,$field);
				if($exp[0] == ':m'.$this->label){
					$buff .= $this->fields->replaceVars($field, true).',';
					$buff2 .= $field.',';
				}
			}
			$this->appendQueryBuff('('.substr($buff, 0, -1).') VALUES ('.substr($buff2, 0, -1).')');
		}
	private function makeSelect(){
			$this->appendQueryBuff($this->queryType);
			$fields = $this->tables->getFields(false);
			foreach($fields as $key => $val)
				if(!is_bool($pos = strpos($val, '('))){
					list($val, $label) = explode(' AS ', $val);
					$command = substr($val, 0, $pos);
					$command = explode('.', $command);
					$val = substr($val, $pos+1, strlen($val)-($pos+2));
					if($val == '*') $as = $command[1];
					else{
						$val = $as = $command[0].'.'.$val;
					}
					$fields[$key] = $command[1].'('.$val.') AS '.$label;
				}
				else if(is_bool(strpos($val, '*')))
					$fields[$key] = $val;
			$fields = implode(',', $fields);
			$this->appendQueryBuff($fields.' FROM '.$this->name);
		}
	private function loopJoin($cb){
		for($i = 0; $i < count($this->objJoin); $i++){
			$obj = $this->objJoin[$i];
			if(is_callable($cb)) $cb($obj);
		}
		return $this;
	}
	public function makeJoin(){
		$rt = '';
		$this->loopJoin(function($obj) use(&$rt){
			$ctx = $obj->context;
			$rt .= $obj->type.' JOIN '.$ctx->name.' ON '.$this->label.'.'.$obj->relation[1].'='.$ctx->label.'.'.$obj->relation[0].' ';
			$rt .= $ctx->makeJoin().' ';
		});
		return $rt;
	}
	private function makeWhere(){
			if(count($this->where) > 0) $this->appendQueryBuff('WHERE');
            $i = 0;
			foreach($this->where as $obj){
				if(!is_bool($pos = strpos($obj[0], '('))){
					$command = substr($obj[0], 0, $pos);
					$obj[0] = substr($obj[0], $pos+1, strlen($obj[0])-($pos+2));
					$field = $command.'('.$obj[0].')';
				}else $field = $obj[0];
				$this->appendQueryBuff($field.' '.$obj[1].' :where_'.$obj[0].(($i != count($this->where)-1)?(' '.$obj[3]):''));
				$i++;
			}
    }
	private function loopFields($cb = NULL, &$rt = [], $ctx = NULL, bool &$loopField = true){
		if(!is_a($ctx, get_class($this))) $ctx = $this;
		foreach($ctx->fields as $table => $value){
			if($loopField){
				foreach($value as $field => $value){
					if(is_callable($cb)) $cb($field, $value, $table);
					$rt[$table.'.'.$field] = $value;
				}
			}
		}
		return $rt;
	}
	private function join($context, $relation, $operator = '=', $type, $label = NULL){
		$join = new MySQLJoin($context, $relation, $operator, $type, $label);
		$this->tables[] = $join->context->tables;
		$this->objJoin[] = $join;
		return $this;
	}
	private function endQB(){
		$this->appendQueryBuff(";\n");
		return $this;
	}
	private function appendQueryBuff($text, bool $end = false){
		if(substr($this->queryBuff, -1) == ';') $this->queryBuff = trim(substr($this->queryBuff, 0, -1));
		$this->queryBuff .= ' '.trim($text).((substr($text, -1) != ';')?';':'');
		if($end) $this->endQB();
		return $this;
	}
}
?>