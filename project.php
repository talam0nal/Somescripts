<?php

namespace app;

use app\models\page;

class project extends page
{	
	public function index()
	{
		redirect("/");
	}
	
	public function single($url)
	{
      if (!isset($_SESSION['user'])) 
    	{
        redirect("/login");
    	}
    
    	$this->checkUser($url);

		$sql = "
			SELECT 
				*
			FROM
				tcms_projects
			WHERE
				id = '$url'
			";
		$this->db->query($sql);
		$row = $this->db->fetchrow();
		navigation_link("", $row['title']);	
		

		$sql = "
			SELECT 
				*
			FROM
				tcms_mockups
			WHERE
				project = '$url'
			";
		$this->db->query($sql);
		while ($row = $this->db->fetchrow())
		{
			$this->template->append('mockups', $row);
		}

		//Выбираем из базы нужные нам страницы с вёрсткой
		$sql = "
			SELECT 
				*
			FROM
				tcms_makeups
			WHERE
				project = '$url'
			";
		$this->db->query($sql);
		$allmakeups = $this->db->fetchall();
		foreach ($allmakeups as $key => $value) {
			$makeups=$allmakeups[$key]['id'];

			//После этого выбираем из базы бейджи, которые относятся к этой вёрстке
			$sql="
			SELECT
				*
			FROM 
				tcms_changes
			WHERE mockup_id = $makeups
			AND type = 2
			";
			$this->db->query($sql);
			$badges=array();
			while ($row = $this->db->fetchrow())
			{
				$badges[]=$row['id']; //Запихиваем айдишники с бейджами в массив
			}

			//Выбираем из базы комменты, которые относятся к этим бейджам
			if($badges) {
				$sql="
				SELECT
					*
				FROM 
					tcms_comments
				WHERE change_id IN (".implode($badges, ",").")
				";
				$this->db->query($sql);
				$i=0;
				while ($row = $this->db->fetchrow())
				{
					$i++;
				}
				$allmakeups[$key]['commentscount']=$i;
			}
			$this->template->append('makeups', $allmakeups[$key]);
			unset($i);
		}


	}
	public function checkUser($project_id)
	{
		$user=$_SESSION['user']['id'];
	    $sql="
		    SELECT
		    	*
		    FROM 
		    	tcms_clients
		    WHERE id = '$user'
		    ";
	    $this->db->query($sql);
	    $row = $this->db->fetchrow();
	    $kk=substr_count($row['project'], "|$project_id|");

	    if(!$kk) 
	    {
	    	redirect('/');
	    }
	}



}
