<?php

// В этой библиотеке функция выполнения CURL
require_once('onlineedu_lib.php');
require(__DIR__.'/../../../config.php');
require_once($CFG->libdir.'/adminlib.php');

// Если не администратор, то показывать пустую страницу
if (!has_capability('moodle/site:config', context_system::instance())) {
    // Do not throw exception display an empty page with administration menu if visible for current user.
    $PAGE->set_title($SITE->fullname);
    $PAGE->set_heading($SITE->fullname);
    echo $OUTPUT->header();
    echo $OUTPUT->footer();
    exit;
}


$PAGE->requires->css(new moodle_url('/admin/tool/scoss/src/style.css'));

$partner_id = "ffb79db3-f762-498c92b0-42fb7f4a8095"; //Идентификатор Платформы

$PAGE->requires->css(new moodle_url('/admin/tool/send_course_cards/src/style.css'));
$PAGE->set_title(get_string('scoss_send_course_cards', 'tool_scoss'));
$PAGE->set_heading(get_string('scoss_send_course_cards', 'tool_scoss'));
echo $OUTPUT->header();


// $sqlenroluser = "SELECT @I:=@I+1 AS aa, mu.id, mu.lastname, maoll.userid, maoll.username, mc.id mcid,
$sqlenroluser = "SELECT @I:=@I+1 AS aa, mu.id, mu.lastname, mc.id mcid,
            DATE_FORMAT( FROM_UNIXTIME(mue.timecreated),'%Y-%m-%dT%T') DATETIME, mcd.value id_course_onlineedu, mcd2.value session_onlineedu
            FROM mdl_user mu

            -- JOIN mdl_auth_oauth2_linked_login maoll ON maoll.userid=mu.id AND maoll.issuerid=(SELECT moi.id FROM mdl_oauth2_issuer moi WHERE moi.baseurl like '%online.edu%')  -- только те кто зашел с онлайнеду

            JOIN mdl_user_enrolments mue ON mue.userid = mu.id -- далее определение курса и только студенты
            JOIN mdl_enrol me ON me.id = mue.enrolid
            JOIN mdl_course mc ON mc.id = me.courseid
            JOIN mdl_context mc1 ON mc1.instanceid = mc.id AND mc1.contextlevel = 50
            JOIN mdl_role_assignments mra ON mra.contextid = mc1.id AND mra.roleid = 5 AND mra.userid = mu.id

            JOIN mdl_customfield_data mcd ON mcd.instanceid = mc.id -- только курсы зарегиные на онлайнеду
            JOIN mdl_customfield_field mcf ON mcf.id = mcd.fieldid  AND mcf.shortname = 'id_course_onlineedu' -- идентификатор курса в кастомных полях
            
            JOIN mdl_customfield_data mcd2 ON mcd2.instanceid = mc.id -- определение сессии
            JOIN mdl_customfield_field mcf2 ON mcf2.id = mcd2.fieldid  AND mcf2.shortname = 'session_onlineedu' -- идентификатор сессии курса в onlineedu
            
            JOIN mdl_customfield_data mcd3 ON mcd3.instanceid = mc.id AND mcd3.value = 1-- отправка по которым включена
            JOIN mdl_customfield_field mcf3 ON mcf3.id = mcd3.fieldid  AND mcf3.shortname = 'transfer_onlineedu'

            -- те кто еще не был отправлен в систему ранее / под той же сессией
            LEFT JOIN mdl_transfer_onlineedu tole ON tole.userid = mu.id AND tole.act = 'enrol' AND tole.session_id LIKE mcd2.value

            JOIN (SELECT @I := 0) aa
            WHERE tole.id IS NULL
            -- AND mu.id IN(496,2553)
            ";



if (isset($_POST)) {
    try{
        $result = $DB->get_records_sql($sqlenroluser);

        mtrace("Запрос к базе данных прошел успешно");	
        foreach($result as $rec)
        {
            mtrace('-- USER ', $rec->id,' --', $jsonstr); // Заменить id на userid
            
            $json = array();
            
            $datestr = date('c', strtotime($rec->datetime));
            //$datestr='2020-09-11T22:55:03+0300';
            
            // Объект с информацией о студенте. 
            $json = array(
                "course_id" => $rec->id_course_onlineedu,
                "session_id" => $rec->session_onlineedu, 
                "user_id" => "sdss-dss2-ds23", // Заменить на: "user_id" => $rec->username,
                "enroll_date" => $datestr
            );
            
            $jsonstr = json_encode($json);
            
            // Вызов функции выполнения CURL
            // $curlresp = curlsend('/api/v2/courses/participation', $jsonstr, 'POST'); 
            $curlresp = curlsend('/test_moodle', $jsonstr, 'POST'); // Временное использование заменить на строчку выше

            // Есть ошибка отправляем сообщение на почту
            if($curlresp['httpcode'] != 201 && $curlresp['httpcode'] != 200){
                mtrace('Выполнено с ошибкой HTTP_CODE ', $curlresp['httpcode']);
                $errormail = true;
                continue;	// Переходим к следующему юзеру
            }
            // Ошибки нет, проводим запись в таблицу логов
            // Если на этапе записи произойдет проблема, то первый запрос вновь отправит даннх пользователей на регистрацию. Должно быть все хорошо
            else{
                // -- сохранять в таблице transfer_onlineedu только после положительного ответа
                //var_dump('Выполняем запрос');
                mtrace('Curl Успешно');
                
                $sqlinsert = "INSERT INTO mdl_transfer_onlineedu
                (userid, user_scos, act, date_transfer, courseid, course_scos_id, session_id)
                VALUES (:vuserid, :vuser_scos, 'enrol', now(), :vcourseid, :vcourse_scos_id, :vsessionid)";
                
                try {
                    $insertResult = $DB->execute($sqlinsert, array(
                    'vuserid' => $rec->id, // Заменить id на userid
                    'vuser_scos' => "sdss-dss2-ds23", // Заменить на: "vuser_scos" => $rec->username,
                    'vcourseid' => $rec->mcid,
                    'vcourse_scos_id' => $rec->id_course_onlineedu,
                    'vsessionid' => $rec->session_onlineedu));	
                    
                    mtrace('Запись в БД прошла успешно <br>');
                } 
                catch(\Exception $e) 
                {
                    mtrace('Ошибка записи в БД: ' . $e);
                    $errormail = true;
                    continue;	// Переходим к следующему юзеру если ошибка
                }
            }
            
        }
    }
    catch (\Exception $ex) // Здесь фокус в обратном слеше
    {						
        mtrace("Ошибка выполнения запроса к базе данных: " . $ex);	
        $errormail = true;
        //exit();
    }

    /*  отсылаем себе сообщение на почту один раз за весь скрипт. 
    Поэтому если будет проблема с несколькими пользователями, то смотреть в результатах выполнения задачи*/
    // if ($errormail){
    //     errormail('Ошибка отправки пользователей на online edu');
    // }

    $output = $PAGE->get_renderer('tool_scoss');
    $page = new \tool_scoss\output\show_table_edu();
    echo $output->render($page);
}

echo $OUTPUT->footer();

/*
// !!!!!!!!! Задача 2. Запись пользователй на курс в online.edu !!!!!!!!!
    try{
        // Получение списка студентов
        $students = get_enrolled_users(context_course::instance($id), 'moodle/competency:coursecompetencygradable');
        
        // Получение дополнительных полей курса
        $fields = $DB->get_records_list('customfield_field', 'shortname', array('transfer_onlineedu', 'id_course_onlineedu', 'session_onlineedu'));
        foreach ($fields as $field) {
            $fieldvalues[$field->shortname] = $DB->get_record('customfield_data', array('fieldid' => $field->id, 'instanceid' => $id), 'value');
        }
    
        // Текущее время unixtime
        $now = time();
    
        mtrace("Запрос к базе данных прошел успешно");
    
        if ($fieldvalues['transfer_onlineedu'] == 1){
            // Массив хранения данных о пользователях для записи на курс
            $mas_data_about_users = [];
    
            mtrace("Начало записи на курс $id");
            foreach($students as $student)
            {               
                $datestr = date('c', strtotime($rec->datetime)); //$datestr='2020-09-11T22:55:03+0300';
                
                // Объект с информацией о студенте. 
                $json_data_user = array(
                "course_id" => $id,
                "session_id" => $fieldvalues['session_onlineedu']->value, 
                "user_id" => $student->id, // !!! временно должно быть id из СЦОС
                "enroll_date" => $datestr,
                "session_start" => $course_start, // !!! временно должно быть дата начала курса
                "session_end" => $course_end, // !!! временно должно быть дата окончания курса
                );
                
                array_push($mas_data_about_users, json_encode($json_data_user));
            }
    
            // Вызов функции выполнения CURL
            // $curlresp = curlsend('/api/v2/courses/enroll', $mas_data_about_users, 'POST');
            
            // Проверка на возникновение ошибок
            if($curlresp['httpcode']!=201){
                // Есть ошибка отправляем сообщение на почту
                mtrace('Запрос записи на курс $id выполнено с ошибкой HTTP_CODE ', $curlresp['httpcode']);
                $errormail = true;
            }
            else{
                // Ошибки нет, проводим запись в таблицу логов
                // Если на этапе записи произойдет проблема, то первый запрос вновь отправит даннх пользователей на регистрацию. Должно быть все хорошо
                // -- сохранять в таблице transfer_onlineedu только после положительного ответа
                //var_dump('Выполняем запрос');
                mtrace("Curl Успешно выполнен. Пользователи записаны на курс $id.");
            }
        }
        else {
            mtrace("Курс с id - $id не требует отправки на online.edu.");
        }
    }
    catch (\Exception $ex) // Здесь фокус в обратном слеше
    {						
        mtrace("Ошибка выполнения запроса к базе данных: ".$ex);	
        $errormail=True;
        //exit();
        
        // Это функция наша отправки сообщений внутри СДО. Библиотеки нет в этом плагине
        // \tool_discdorequestprocess_ex_api::SendMessage(22,22,$errMsg,'1');
    }

*/