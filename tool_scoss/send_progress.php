<?php

// В этой библиотеке функция выполнения CURL
require_once('onlineedu_lib.php');
require(__DIR__.'/../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
$PAGE->requires->css(new moodle_url('/admin/tool/scoss/src/style.css'));

// Если не администратор, то показывать пустую страницу
if (!has_capability('moodle/site:config', context_system::instance())) {
    // Do not throw exception display an empty page with administration menu if visible for current user.
    $PAGE->set_title($SITE->fullname);
    $PAGE->set_heading($SITE->fullname);
    echo $OUTPUT->header();
    echo $OUTPUT->footer();
    exit;
}

$partner_id = "ffb79db3-f762-498c92b0-42fb7f4a8095"; //Идентификатор Платформы

$PAGE->requires->css(new moodle_url('/admin/tool/send_course_cards/src/style.css'));
$PAGE->set_title(get_string('scoss_send_course_cards', 'tool_scoss'));
$PAGE->set_heading(get_string('scoss_send_course_cards', 'tool_scoss'));
echo $OUTPUT->header();



if (isset($_POST)) {
    /* описание в задаче по отправке*/
    /*
        Задача делится на две части: 
        1. отправка оценок по заданиям включает также отправку общего прогресса по курсу
        2. отправка только прогресса. Сделана отдельно, т.к. не всегда оценка по заданиям автоматически генерирует прогресс
        
        Примечание. Таблица transfer_onlineedu должна гарантировать, что не будут записываться одинаковые результаты обучения
    */	
    
    $errormail=false; // скидываем флаг отправки почтового сообщения
    /*
        -----  1. отправка оценок.---------------------------------------------------------------
        Внимание особенности
        Не передаются данные из категорий журнала оценок
        По модулям передается процент который расчитывается просто n/max*100 . т.е. никакие спец возможности мудла не анализируются формулы, смещения и т.д.
    */
    mtrace('-- Отправляем текущие результаты -- ');
    
    // $sqltransfer="SELECT @I:=@I+1 AS aa, mu.id muid,mu.lastname, maoll.username usiaId,
    $sqltransfer="SELECT @I:=@I+1 AS aa, mu.id muid,mu.lastname,
    mc.id mcid,mcd.value id_course_onlineedu, mcd2.value session_onlineedu,
    mgg.finalgrade progress, mgi2.itemname checkpointName, mgi2.iteminstance rating_id_instance, 
    (mgg2.finalgrade/mgi2.grademax*100) rating, mgg2.finalgrade, mgg2.timemodified rating_time
    
    FROM mdl_course mc  -- определяем курс
    JOIN mdl_customfield_data mcd ON mcd.instanceid=mc.id -- только курсы зарегиные на онлайнеду
    JOIN mdl_customfield_field mcf ON mcf.id=mcd.fieldid  AND mcf.shortname='id_course_onlineedu' -- идентификатор курса в кастомных полях
    
    JOIN mdl_customfield_data mcd2 ON mcd2.instanceid=mc.id -- определение сессии
    JOIN mdl_customfield_field mcf2 ON mcf2.id=mcd2.fieldid  AND mcf2.shortname='session_onlineedu' -- идентификатор сессии курса в onlineedu
    
    JOIN mdl_customfield_data mcd3 ON mcd3.instanceid=mc.id AND mcd3.value=1-- отправка по которым включена
    JOIN mdl_customfield_field mcf3 ON mcf3.id=mcd3.fieldid  AND mcf3.shortname='transfer_onlineedu' 
    
    JOIN mdl_context mc1 ON mc1.instanceid=mc.id  AND mc1.contextlevel=50 -- только студенты
    JOIN mdl_role_assignments mra ON  mra.contextid=mc1.id AND mra.roleid=5  -- это лишний запрос, т.к. в таблицу могут попавть только студенты. Перестраховка
    JOIN mdl_user mu ON mra.userid=mu.id
    
    JOIN mdl_transfer_onlineedu tole ON tole.userid=mu.id AND tole.act='enrol' AND tole.session_id=mcd2.value -- только те кого уже отправили на online edu под текущей сессией
    -- JOIN mdl_auth_oauth2_linked_login maoll ON maoll.userid=mu.id AND maoll.issuerid=(SELECT moi.id FROM mdl_oauth2_issuer moi WHERE moi.baseurl like '%online.edu%') -- Важно! идентификатор oauth
    
    JOIN mdl_grade_items mgi ON mgi.courseid=mc.id AND mgi.itemtype='course' -- оценка за курс
    JOIN mdl_grade_grades mgg ON mgg.itemid=mgi.id AND mgg.userid=mu.id
    JOIN mdl_grade_items mgi2 ON mgi2.courseid=mc.id AND mgi2.itemtype='mod' -- оценка за элемент
    JOIN mdl_grade_grades mgg2 ON mgg2.itemid=mgi2.id AND mgg2.userid=mu.id
    
    -- ищем оценки, которые не были еще отправлены
    LEFT JOIN mdl_transfer_onlineedu tole2 ON tole2.userid=mu.id 
    AND tole2.rating_time=mgg2.timemodified 
    AND tole2.rating_id_instance=mgi2.iteminstance -- ищем только новье
    AND tole2.courseid=mc.id
    AND tole2.act='checkpoint'
    
    JOIN (SELECT @I := 0) aa -- для нумерации строк требование мудл
    
    WHERE mgg2.finalgrade IS NOT NULL
    AND tole2.id IS NULL
    -- AND mu.id IN(496,2553, 4575)
    ";
    
    try{
        $result = $DB->get_records_sql($sqltransfer);
        mtrace("Чекпоинт Запрос к базе данных прошел успешно");	
        //var_dump($result); exit();
        foreach($result as $rec)
        {
            mtrace('-- USER -- '.$rec->muid);
            
            $json = array();
            
            // $datestr = date( 'c', strtotime($rec->rating_time));
            $datestr = date( 'c', $rec->rating_time);
            //$datestr ='2020-09-11T22:55:03+0300';
            
            $json = array(
                "course_id" => $rec->id_course_onlineedu,
                "session_id" => $rec->session_onlineedu,
                "user_id" => "sdss-dss2-ds23", // Заменить на: "user_id" => $rec->usiaid,
                "date" => $datestr,
                "rating" => $rec->rating,
                "checkpoint_name" => $rec->checkpointname,
                "checkpoint_id" => $rec->rating_id_instance,
                "progress" => $rec->progress
            );
            
            $jsonstr = json_encode($json);
            mtrace($jsonstr);
            
            // Вызов функции выполнения CURL
            // $curlresp = curlsend('/api/v2/courses/results', $jsonstr, 'POST');
            $curlresp = curlsend('/test_moodle', $jsonstr, 'POST');
            
            if($curlresp['httpcode']!=201 && $curlresp['httpcode']!=200){
                //mtrace('Выполнено с ошибкой HTTP_CODE'.$response);
                mtrace('Выполнено с ошибкой HTTP_CODE ', $curlresp['httpcode']);
                $errormail=true;
                continue;	// Переходим к следующему юзеру
            }
            else
            {
                // Ошибки нет, проводим запись в таблицу логов    
                mtrace('Curl Успешно');
                
                $sqlinsert="INSERT INTO mdl_transfer_onlineedu
                (userid, user_scos,	act, date_transfer, courseid, course_scos_id, session_id, 
                rating, progress, rating_time, rating_id_instance, checkpoint_name)
                
                VALUES (:vuserid, :vuser_scos, 'checkpoint', now(), :vcourseid, :vcourse_scos_id, :vsessionid,
                :rating, :progress, :rating_time, :rating_id_instance, :checkpointname)";
                try {
                    $insertarray = array(
                        'vuserid' => $rec->muid,
                        'vuser_scos' => "sdss-dss2-ds23", // Заменить на: 'vuser_scos' => $rec->usiaid,
                        
                        'vcourseid' => $rec->mcid,
                        'vcourse_scos_id' => $rec->id_course_onlineedu,
                        'vsessionid' => $rec->session_onlineedu,
                        
                        'rating' => $rec->rating, 
                        'progress' => $rec->progress, 
                        'rating_time' => $rec->rating_time, 
                        'rating_id_instance' => $rec->rating_id_instance, 
                        'checkpointname' => $rec->checkpointname
                    );

                    $insertResult = $DB->execute($sqlinsert, $insertarray);	
                    
                    mtrace("Запись в базу успешно <br>");	
                }
                catch(\Exception $e) 
                {
                    mtrace('Ошибка записи в базу данных : '/*.$e*/);
                    // var_dump($insertarray);
                    $errormail=true;
                    continue;	// Переходим к следующему юзеру
                }
            }
            
        }
    }
    
    catch (\Exception $ex) 
    {			
        mtrace("Ошибка выполнения запроса к базе данных: ".$ex);
        // errormail('Ошибка выполнения запроса к БД отправки результатов пользователей');
        // $errormail=true;
    }



    // -----  2. отправка только прогресса.---------------------------------------------------------------

    mtrace('-- Отправляем только прогресс -- ');
    // $sqltransfer="SELECT @I:=@I+1 AS aa, mu.id muid,mu.lastname, maoll.username usiaId, 
    $sqltransfer="SELECT @I:=@I+1 AS aa, mu.id muid, mu.lastname,
    mc.id mcid, mcd.value id_course_onlineedu, mcd2.value session_onlineedu,
    mgg.finalgrade progress, mgg.timemodified
    
    FROM mdl_course mc  -- определяем курс
    JOIN mdl_customfield_data mcd ON mcd.instanceid=mc.id -- только курсы зарегиные на онлайнеду
    JOIN mdl_customfield_field mcf ON mcf.id=mcd.fieldid AND mcf.shortname='id_course_onlineedu' -- идентификатор курса в кастомных полях
    
    JOIN mdl_customfield_data mcd2 ON mcd2.instanceid=mc.id -- определение сессии
    JOIN mdl_customfield_field mcf2 ON mcf2.id=mcd2.fieldid AND mcf2.shortname='session_onlineedu' -- идентификатор сессии курса в onlineedu
    
    JOIN mdl_customfield_data mcd3 ON mcd3.instanceid=mc.id AND mcd3.value=1-- отправка по которым включена
    JOIN mdl_customfield_field mcf3 ON mcf3.id=mcd3.fieldid AND mcf3.shortname='transfer_onlineedu' 
    
    JOIN mdl_context mc1 ON mc1.instanceid=mc.id  AND mc1.contextlevel=50 -- только студенты
    JOIN mdl_role_assignments mra ON  mra.contextid=mc1.id AND mra.roleid=5  -- это лишний запрос, т.к. в таблицу могут попавть только студенты. Перестраховка
    JOIN mdl_user mu ON mra.userid=mu.id
    
    JOIN mdl_transfer_onlineedu tole ON tole.userid=mu.id AND tole.act='enrol' AND tole.session_id=mcd2.value -- только те кого уже отправили на online edu под текущей сессией
    -- JOIN mdl_auth_oauth2_linked_login maoll ON maoll.userid=mu.id AND maoll.issuerid=(SELECT moi.id FROM mdl_oauth2_issuer moi WHERE moi.baseurl like '%online.edu%') -- Важно! идентификатор oauth
    
    JOIN mdl_grade_items mgi ON mgi.courseid=mc.id AND mgi.itemtype='course' -- оценка за курс
    JOIN mdl_grade_grades mgg ON mgg.itemid=mgi.id AND mgg.userid=mu.id
    
    -- ищем оценки, которые не были еще отправлены
    LEFT JOIN mdl_transfer_onlineedu tole2 ON tole2.userid=mu.id 
    AND tole2.rating_time=mgg.timemodified 
    AND tole2.courseid=mc.id
    AND tole2.act='progress'
    
    JOIN (SELECT @I := 0) aa -- для нумерации строк требование мудл
    
    WHERE mgg.finalgrade IS NOT NULL
    AND tole2.id IS NULL
    -- AND mu.id IN(496,2553, 4575)
    ";
    
    try{
        $result=$DB->get_records_sql($sqltransfer);
        mtrace("Прогресс Запрос к базе данных прошел успешно");	
        //var_dump($result); exit();
        foreach($result as $rec)
        {
            mtrace('-- USER -- '.$rec->muid);
            
            $json = array();
            
            $json= array(
                "courseId" => $rec->id_course_onlineedu,
                "sessionId" => $rec->session_onlineedu,
                "usiaId" => "sdss-dss2-ds23", // Заменить на: "usiaId" => $rec->usiaid,
                "progress" => $rec->progress
            );
            
            $jsonstr= json_encode($json);
            mtrace($jsonstr);
            
            // Вызов функции выполнения CURL
            // $curlresp=curlsend('/api/v1/course/results/progress/add',$jsonstr, 'POST');
            $curlresp = curlsend('/test_moodle', $jsonstr, 'POST');

            if($curlresp['httpcode']!=201 && $curlresp['httpcode']!=200){
                //mtrace('Выполнено с ошибкой HTTP_CODE'.$response);
                mtrace('Выполнено с ошибкой HTTP_CODE ', $curlresp['httpcode']);
                $errormail=true;
                continue;	// Переходим к следующему юзеру
                
            }
            // Ошибки нет, проводим запись в таблицу логов
            else{
                
                mtrace('Curl Успешно');
                
                $sqlinsert="INSERT INTO mdl_transfer_onlineedu
                (userid, user_scos,	act, date_transfer, courseid, course_scos_id, session_id, progress, rating_time)
                
                VALUES (:vuserid,	:vuser_scos,  'progress',  now(),  :vcourseid,  :vcourse_scos_id,  :vsessionid,
                :progress, :ratingtime)";
                try {
                    $insertarray=array(
                        'vuserid' => $rec->muid,
                        'vuser_scos' => "sdss-dss2-ds23", // Заменить на: 'vuser_scos' => $rec->usiaid,
                        'vcourseid' => $rec->mcid,
                        'vcourse_scos_id' => $rec->id_course_onlineedu,
                        'vsessionid' => $rec->session_onlineedu,
                        'progress' => $rec->progress, 
                        'ratingtime' => $rec->timemodified
                    );

                    $insertResult = $DB->execute($sqlinsert, $insertarray);	
                    
                    mtrace("Запись в базу успешно <br>");	
                    
                    } catch(\Exception $e) {
                    mtrace('Ошибка записи в базу данных : '.$e);
                    // var_dump($insertarray);
                    $errormail=true;
                    
                    continue;	// Переходим к следующему юзеру
                }
            }
            //break;
        }
    }
    
    catch (\Exception $ex) 
    {			
        mtrace("Ошибка выполнения запроса к базе данных: ".$ex);
        errormail('Ошибка выполнения запроса к БД отправки прогресса пользователей');
        $errormail=true;
        
    }

    $output = $PAGE->get_renderer('tool_scoss');
    $page = new \tool_scoss\output\show_table_edu();
    echo $output->render($page);
}

echo $OUTPUT->footer();