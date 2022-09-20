<?php

namespace App\Service;

use SQLite3;
use PDO;
use DOMDocument;
use Exception;

class SearchEngine {
	private $host;
	private $dbname;
	private $user;
	private $password;
	private $dsn;
	private $filename;
	private $perPage;
	private $type = "text";
	
	public function setParams($host, $dbname, $user, $password, $filename, $perPage = null) {
		$this->host = $host;
		$this->dbname = $dbname;
		$this->user = $user;
		$this->password = $password;
		$this->filename = $filename;
		$this->perPage = $perPage;
		
		$this->dsn = "mysql:host=${host};dbname=${dbname};charset=UTF8";
	}
	
	public function setType(string $type) {
		$this->type = $type;
	}
	
	public function init(string $filename) {
		$db = new SQLite3($filename);
		$db->exec("CREATE VIRTUAL TABLE IF NOT EXISTS doclist USING FTS5(id, classname, searchText, language)");
		$db->exec("CREATE VIRTUAL TABLE IF NOT EXISTS imagelist USING FTS5(id, classname, imagePath, language)");
		$db = null;
	}
	
	public function run(string $filename, string $query, string $classname) {
		$pdo = new PDO($this->dsn, $this->user, $this->password);
		
		if ($pdo) {
			$statement = $pdo->query($query);
			$datas = $statement->fetchAll(PDO::FETCH_ASSOC);
			
			$pdoSqlite = new PDO("sqlite:{$this->filename}", null, null, [PDO::ATTR_PERSISTENT => true]);

			foreach($datas as $data) {
				if(!isset($data["id"]))
					throw new Exception("Field 'id' must be specified");
				
				$id = $data["id"];
				
				if(!isset($data["language"]))
					throw new Exception("Field 'language' must be specified");
				
				$language = $data["language"];

				unset($data["id"]);
				unset($data["language"]);
				
				$text = $this->sanitizeDataArray($data);
				
				$pdoSqlite->exec("INSERT INTO doclist (id, classname, searchText, language) VALUES (${id}, '${classname}', '${text}', '${language}')");
			}
		}
		
		$pdo = null;
		$pdoSqlite = null;
	}
	
	public function runImage(string $filename, string $query, string $classname) {
		$pdo = new PDO($this->dsn, $this->user, $this->password);

		if ($pdo) {
			$statement = $pdo->query($query);
			
			if(!$statement)
				return;
			
			$datas = $statement->fetchAll(PDO::FETCH_ASSOC);
			
			$pdoSqlite = new PDO("sqlite:{$this->filename}", null, null, [PDO::ATTR_PERSISTENT => true]);

			foreach($datas as $data) {
				if(!isset($data["id"]))
					throw new Exception("Field 'id' must be specified");
				
				$id = $data["id"];
				
				if(!isset($data["language"]))
					throw new Exception("Field 'language' must be specified");
				
				$language = $data["language"];

				unset($data["id"]);
				unset($data["language"]);

				foreach($data as $d) {
					if(preg_match('/\.(jpe?g|png|gif|bmp)$/i', $d))
						$pdoSqlite->exec("INSERT INTO imageList (id, classname, imagePath, language) VALUES (${id}, '${classname}', '${d}', '${language}')");
					else {
						libxml_use_internal_errors(true);
						$dom = new DOMDocument();
						$dom->loadHTML($d);

						foreach ($dom->getElementsByTagName('img') as $i => $img) {
							$pdoSqlite->exec("INSERT INTO imageList (id, classname, imagePath, language) VALUES (${id}, '${classname}', '{$img->getAttribute('src')}', '${language}')");
						}
					}
				}
			}
		}
		
		$pdo = null;
		$pdoSqlite = null;
	}
	
	public function search(?string $keyword = null, ?string $language = null, ?string $classname = null, ?int $pageNumber = 0) {
		$this->type = "text";
		return $this->genericSearch($keyword, $language, $classname, $pageNumber);
	}
	
	public function searchImage(?string $keyword = null, ?string $language = null, ?string $classname = null, ?int $pageNumber = 0) {
		$this->type = "image";
		return $this->genericSearch($keyword, $language, $classname, $pageNumber);
	}
		
	private function genericSearch(?string $keyword = null, ?string $language = null, ?string $classname = null, ?int $pageNumber = 0) {
		$startTimer = microtime(true);
		
		$pdo = new PDO("sqlite:{$this->filename}", null, null, [PDO::ATTR_PERSISTENT => true]);
		
		$sql = $this->createSearchQuery($keyword, $language, $classname, $pageNumber);

		$statement = $pdo->prepare($sql);
		$statement->execute();
		
		$pdo = null;
		
		$res = $statement->fetchAll(PDO::FETCH_ASSOC);
		
		$stopTimer = microtime(true);
		
		return ["datas" => $res, "execution_time" => round($stopTimer - $startTimer, 7) * 1000];
	}
	
	public function countDatas(?string $keyword = null, ?string $language = null, ?string $classname = null) {
		$pdo = new PDO("sqlite:{$this->filename}", null, null, [PDO::ATTR_PERSISTENT => true]);
		
		$sql = $this->createSearchQuery($keyword, $language, $classname, null, true);
		
		$statement = $pdo->query($sql);
		
		$pdo = null;
		
		return $statement->fetchColumn();
	}
	
	public function getSQLQuery(?string $keyword = null, ?string $language = null, ?string $classname = null, string $select = "*"): String {
		$params = [];
		
		if(!empty($keyword))
			$params[] = "searchText:".SQLite3::escapeString($keyword);
		
		if(!empty($language))
			$params[] = "language:".SQLite3::escapeString($language);
		
		if(!empty($classname))
			$params[] = "classname:".SQLite3::escapeString($classname);
		
		$paramsString = implode(" AND ", $params);
		
		if($this->type == "image") {
			$query = "SELECT ${select} FROM imageList WHERE id || classname IN (SELECT id || classname FROM doclist WHERE doclist MATCH '${paramsString}')";
		} else {
			$query = "SELECT ${select} FROM doclist WHERE doclist MATCH '${paramsString}'"; 
		}

		return $query;
	}
	
	private function createSearchQuery(?string $keyword = null, ?string $language = null, ?string $classname = null, ?int $pageNumber = 0, ?bool $count = false): string {
		$limitString = "";
		
		if(!empty($this->perPage) and !empty($pageNumber)) {
			$limit = $this->perPage;
			$offset = ($pageNumber - 1) * $limit;
			
			$limitString = " LIMIT ${limit} OFFSET ${offset}";
		}
		
		$query = null;
		$select = $count ? "COUNT(*)" : "*";
		
		$query = $this->getSQLQuery($keyword, $language, $classname, $select)." ".$limitString;

		return $query;
	}
	
	public function insertImage(array $data) {
		$pdo = new PDO("sqlite:{$this->filename}", null, null, [PDO::ATTR_PERSISTENT => true]);

		if(!isset($data["id"]))
			throw new Exception("Field 'id' must be specified");
		
		$id = $data["id"];
		
		if(!isset($data["language"]))
			throw new Exception("Field 'language' must be specified");
		
		$language = $data["language"];
		
		if(!isset($data["classname"]))
			throw new Exception("Field 'classname' must be specified");
		
		$classname = $data["classname"];

		unset($data["id"]);
		unset($data["language"]);
		unset($data["classname"]);

		$pdo->exec("DELETE FROM imageList WHERE id = ${id} AND classname = '{$classname}'");

		foreach($data as $d) {
			if(preg_match('/\.(jpe?g|png|gif|bmp)$/i', $d))
				$pdo->exec("INSERT INTO imageList (id, classname, imagePath, language) VALUES (${id}, '${classname}', '${d}', '${language}')");
			else {
				$dom = new DOMDocument();
				$dom->loadHTML($d, LIBXML_NOERROR);

				foreach ($dom->getElementsByTagName('img') as $i => $img) {
					$pdo->exec("INSERT INTO imageList (id, classname, imagePath, language) VALUES (${id}, '${classname}', '{$img->getAttribute('src')}', '${language}')");
				}
			}
		}
			
		
		$pdo = null;
	}
	
	public function insert(array $data) {
		$pdo = new PDO("sqlite:{$this->filename}", null, null, [PDO::ATTR_PERSISTENT => true]);
		
		if(!isset($data["id"]))
			throw new Exception("Field 'id' must be specified");
		
		$id = $data["id"];
		
		if(!isset($data["language"]))
			throw new Exception("Field 'language' must be specified");
		
		$language = $data["language"];
		
		if(!isset($data["classname"]))
			throw new Exception("Field 'classname' must be specified");
		
		$classname = $data["classname"];
		
		$statement = $pdo->prepare("SELECT * FROM doclist WHERE id = ${id} AND classname = '{$classname}'");
		$statement->execute();
		
		if($statement->fetchColumn() !== false) {
			$this->update($data);
		} else {
			unset($data["id"]);
			unset($data["language"]);
			unset($data["classname"]);

			$text = $this->sanitizeDataArray($data);

			$pdo->exec("INSERT INTO doclist (id, classname, searchText, language) VALUES (${id}, '${classname}', '${text}', '${language}')");
		}
	
		$pdo = null;
	}
	
	public function update(array $data) {
		$pdo = new PDO("sqlite:{$this->filename}", null, null, [PDO::ATTR_PERSISTENT => true]);
		
		if(!isset($data["id"])) {
			throw new Exception("Field 'id' must be specified");
		}
		
		$id = $data["id"];
		
		if(!isset($data["language"]))
			throw new Exception("Field 'language' must be specified");
		
		$language = $data["language"];
		
		if(!isset($data["classname"]))
			throw new Exception("Field 'classname' must be specified");
		
		$classname = $data["classname"];

		unset($data["id"]);
		unset($data["language"]);
		unset($data["classname"]);

		$text = $this->sanitizeDataArray($data);
		
		$pdo->exec("UPDATE doclist SET classname = '${classname}', searchText = '${text}', language = '${language}' WHERE id = ${id}");
		
		$pdo = null;
	}
	
	public function delete(int $id, string $classname): void {
		$pdo = new PDO("sqlite:{$this->filename}", null, null, [PDO::ATTR_PERSISTENT => true]);

		$pdo->exec("DELETE FROM doclist WHERE id = ${id} AND classname = '{$classname}'");
		$pdo->exec("DELETE FROM imageList WHERE id = ${id} AND classname = '{$classname}'");
		
		$pdo = null;
	}
	
	private function sanitizeDataArray(array $data): string {
		$data = array_map(function($a) { return preg_replace('/\R/', '', html_entity_decode(strip_tags($a))); }, $data);

		return SQLite3::escapeString(implode(" ", $data));
	}
}