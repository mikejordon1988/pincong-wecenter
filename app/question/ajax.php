<?php
/*
+--------------------------------------------------------------------------
|   WeCenter [#RELEASE_VERSION#]
|   ========================================
|   by Tatfook Network Team
|   © 2011 - 2014 WeCenter. All Rights Reserved
|   http://www.wecenter.com
|   ========================================
|   Support: WeCenter@qq.com
|
+---------------------------------------------------------------------------
*/

define('IN_AJAX', TRUE);

if (!defined('IN_ANWSION'))
{
	die;
}

class ajax extends AWS_CONTROLLER
{
	public function get_access_rule()
	{
		$rule_action['rule_type'] = 'white';

		$rule_action['actions'] = array(
			'get_question_discussions',
			'get_answer_discussions',
			'get_focus_users',
			'get_answer_users'
		);

		return $rule_action;
	}

	public function setup()
	{
		HTTP::no_cache_header();
	}


	public function save_answer_discussion_action()
	{
		if (!$this->user_info['permission']['publish_comment'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的等级还不够')));
		}

		if ($_POST['anonymous'])// AND !$this->user_info['permission']['comment_anonymously'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的等级还不能匿名')));
		}

		if (!check_user_operation_interval('publish', $this->user_id, $this->user_info['permission']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('操作过于频繁, 请稍后再试')));
		}

		$message = my_trim($_POST['message']);
		if (!$message)
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('请输入评论内容')));
		}

        if (!check_repeat_submission($message))
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请不要重复提交')));
        }

		$comment_length_min = intval(get_setting('comment_length_min'));
		if ($comment_length_min AND cjk_strlen($message) < $comment_length_min)
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('评论内容字数不得少于 %s 字', $comment_length_min)));
		}

		$comment_length_max = intval(get_setting('comment_length_max'));
		if ($comment_length_max AND cjk_strlen($message) > $comment_length_max)
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('评论内容字数不得超过 %s 字', $comment_length_max)));
		}

		if (!$this->model('ratelimit')->check_answer_discussion($this->user_id, $this->user_info['permission']['comment_limit_per_day']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('今日评论回复已经达到上限')));
		}

		$answer_info = $this->model('answer')->get_answer_by_id($_GET['answer_id']);
		if (!$answer_info)
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('回复不存在')));
		}
		$question_info = $this->model('question')->get_question_info_by_id($answer_info['question_id']);
		if (!$question_info)
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('问题不存在')));
		}

		if ($question_info['lock'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('不能评论锁定的问题')));
		}

        set_repeat_submission_digest($message);
		set_user_operation_last_time('publish', $this->user_id, $this->user_info['permission']);

		$this->model('answer')->insert_answer_discussion($_GET['answer_id'], $this->user_id, $message);

		H::ajax_json_output(AWS_APP::RSM(array(
			'item_id' => intval($_GET['answer_id']),
			'type_name' => 'answer'
		), 1, null));
	}

	public function save_question_discussion_action()
	{
		if (!$this->user_info['permission']['publish_comment'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的等级还不够')));
		}

		if ($_POST['anonymous'])// AND !$this->user_info['permission']['comment_anonymously'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的等级还不能匿名')));
		}

		if (!check_user_operation_interval('publish', $this->user_id, $this->user_info['permission']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('操作过于频繁, 请稍后再试')));
		}

		$message = my_trim($_POST['message']);
		if (!$message)
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请输入评论内容')));
		}

        if (!check_repeat_submission($message))
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请不要重复提交')));
        }

		$comment_length_min = intval(get_setting('comment_length_min'));
		if ($comment_length_min AND cjk_strlen($message) < $comment_length_min)
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('评论内容字数不得少于 %s 字', $comment_length_min)));
		}

		$comment_length_max = intval(get_setting('comment_length_max'));
		if ($comment_length_max AND cjk_strlen($message) > $comment_length_max)
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('评论内容字数不得超过 %s 字', $comment_length_max)));
		}

		if (!$this->model('ratelimit')->check_question_discussion($this->user_id, $this->user_info['permission']['comment_limit_per_day']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('今日评论问题已经达到上限')));
		}

		$question_info = $this->model('question')->get_question_info_by_id($_GET['question_id']);
		if (!$question_info)
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('问题不存在')));
		}

		if ($question_info['lock'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('不能评论锁定的问题')));
		}

        set_repeat_submission_digest($message);
		set_user_operation_last_time('publish', $this->user_id, $this->user_info['permission']);

		$this->model('question')->insert_question_discussion($_GET['question_id'], $this->user_id, $message);

		H::ajax_json_output(AWS_APP::RSM(array(
			'item_id' => intval($_GET['question_id']),
			'type_name' => 'question'
		), 1, null));
	}


	public function get_focus_users_action()
	{
		if ($focus_users_info = $this->model('question')->get_focus_users_by_question($_GET['question_id'], 18))
		{
			$question_info = $this->model('question')->get_question_info_by_id($_GET['question_id']);

			foreach($focus_users_info as $key => $val)
			{
				$focus_users[$key] = array(
					'uid' => $val['uid'],
					'user_name' => $val['user_name'],
					'avatar_file' => UF::avatar($val, 'mid'),
					'url' => get_js_url('/people/' . $val['url_token'])
				);
			}
		}

		H::ajax_json_output($focus_users);
	}

	public function save_invite_action()
	{
		if (!$this->user_info['permission']['invite_answer'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的等级还不够')));
		}

		if (!$question_info = $this->model('question')->get_question_info_by_id($_POST['question_id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('问题不存在或已被删除')));
		}

		if (!$invite_user_info = $this->model('account')->get_user_info_by_uid($_POST['uid']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('用户不存在')));
		}

		if ($invite_user_info['uid'] == $this->user_id)
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('不能邀请自己回复问题')));
		}

		if ($this->model('question')->has_question_invite($_POST['question_id'], $invite_user_info['uid']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('已邀请过该用户')));
		}

		$this->model('question')->add_invite($_POST['question_id'], $this->user_id, $invite_user_info['uid']);

		$this->model('account')->update_question_invite_count($invite_user_info['uid']);

		$notification_id = $this->model('notify')->send($this->user_id, $invite_user_info['uid'], notify_class::TYPE_INVITE_QUESTION, notify_class::CATEGORY_QUESTION, intval($_POST['question_id']), array(
			'from_uid' => $this->user_id,
			'question_id' => intval($_POST['question_id'])
		));

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	public function get_answer_discussions_action()
	{
		$comments = $this->model('answer')->get_answer_discussions($_GET['answer_id']);

		$user_infos = $this->model('account')->get_user_info_by_uids(fetch_array_value($comments, 'uid'));

		foreach ($comments as $key => $val)
		{
			$comments[$key]['message'] = FORMAT::parse_links($this->model('question')->parse_at_user($comments[$key]['message']));
			$comments[$key]['user_info'] = $user_infos[$val['uid']];
		}

		$answer_info = $this->model('answer')->get_answer_by_id($_GET['answer_id']);

		TPL::assign('question', $this->model('question')->get_question_info_by_id($answer_info['question_id']));
		TPL::assign('answer_info', $answer_info);
		TPL::assign('comments', $comments);

		{
			TPL::output("question/ajax/comments");
		}
	}

	public function get_question_discussions_action()
	{
		$comments = $this->model('question')->get_question_discussions($_GET['question_id']);

		$user_infos = $this->model('account')->get_user_info_by_uids(fetch_array_value($comments, 'uid'));

		foreach ($comments as $key => $val)
		{
			$comments[$key]['message'] = FORMAT::parse_links($this->model('question')->parse_at_user($comments[$key]['message']));
			$comments[$key]['user_info'] = $user_infos[$val['uid']];
		}

		TPL::assign('question', $this->model('question')->get_question_info_by_id($_GET['question_id']));

		TPL::assign('comments', $comments);

		TPL::output("question/ajax/comments");
	}

	public function cancel_question_invite_action()
	{
		$this->model('question')->cancel_question_invite($_GET['question_id'], $this->user_id, $_GET['recipients_uid']);

		$this->model('account')->update_question_invite_count($_GET['recipients_uid']);

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	public function question_invite_delete_action()
	{
		$question_invite_id = intval($_POST['question_invite_id']);

		$this->model('question')->delete_question_invite($question_invite_id, $this->user_id);

		$this->model('account')->update_question_invite_count($this->user_id);

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	public function focus_action()
	{
		if (!$_POST['question_id'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('问题不存在')));
		}

		if (! $this->model('question')->get_question_info_by_id($_POST['question_id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('问题不存在')));
		}

		H::ajax_json_output(AWS_APP::RSM(array(
			'type' => $this->model('question')->add_focus_question($_POST['question_id'], $this->user_id)
		), 1, null));
	}


	// TODO: 重定向功能单独整理出来并支持文章和影片
	public function redirect_action()
	{
		if (! ($this->user_info['permission']['is_administrator'] OR $this->user_info['permission']['is_moderator']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你没有权限进行此操作')));
		}

		if (!check_user_operation_interval_by_uid('modify', $this->user_id, get_setting('modify_content_interval')))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('操作过于频繁, 请稍后再试')));
		}

		$question_info = $this->model('question')->get_question_info_by_id($_POST['item_id']);
		if (!$question_info)
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('内容不存在')));
		}

		if ($question_info['lock'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('锁定的问题不能设置重定向')));
		}

		set_user_operation_last_time_by_uid('modify', $this->user_id);

		$this->model('question')->redirect($this->user_id, $_POST['item_id'], $_POST['target_id']);

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	public function unredirect_action()
	{
		if (! ($this->user_info['permission']['is_administrator'] OR $this->user_info['permission']['is_moderator']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你没有权限进行此操作')));
		}

		if (!check_user_operation_interval_by_uid('modify', $this->user_id, get_setting('modify_content_interval')))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('操作过于频繁, 请稍后再试')));
		}

		$question_info = $this->model('question')->get_question_info_by_id($_POST['item_id']);
		if (!$question_info)
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('内容不存在')));
		}

		if ($question_info['lock'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('锁定的问题不能设置重定向')));
		}

		set_user_operation_last_time_by_uid('modify', $this->user_id);

		$this->model('question')->unredirect($this->user_id, $_POST['item_id'], $_POST['target_id']);

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

/*
	public function remove_question_action()
	{
		if (!$this->user_info['permission']['is_administrator'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('对不起, 你没有删除问题的权限')));
		}

		if ($question_info = $this->model('question')->get_question_info_by_id($_POST['question_id']))
		{
			if ($this->user_id != $question_info['uid'])
			{
				$this->model('account')->send_delete_message($question_info['uid'], $question_info['question_content'], $question_info['question_detail']);
			}

			$this->model('question')->remove_question($question_info['question_id']);
		}

		H::ajax_json_output(AWS_APP::RSM(array(
			'url' => get_js_url('/')
		), 1, null));
	}
*/

	// 只清空不删除
	public function remove_comment_action()
	{
		if (! in_array($_GET['type'], array(
			'answer',
			'question'
		)))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('错误的请求')));
		}

		$comment_id = intval($_GET['comment_id']);
		if (!$comment_id)
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('评论不存在')));
		}

		if ($_GET['type'] == 'answer')
		{
			$comment = $this->model('answer')->get_answer_discussion_by_id($comment_id);
		}
		else if ($_GET['type'] == 'question')
		{
			$comment = $this->model('question')->get_question_discussion_by_id($comment_id);
		}
		if (!$comment || !$comment['message'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('评论不存在')));
		}

		if (! $this->user_info['permission']['edit_question'] AND $this->user_id != $comment['uid'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('你没有权限删除该评论')));
		}

		if ($_GET['type'] == 'answer')
		{
			$this->model('question')->remove_answer_discussion($comment, $this->user_id);
		}
		else if ($_GET['type'] == 'question')
		{
			$this->model('question')->remove_question_discussion($comment, $this->user_id);
		}

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	public function set_best_answer_action()
	{
		if (! $this->user_info['permission']['is_moderator'] AND ! $this->user_info['permission']['is_administrator'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('你没有权限进行此操作')));
		}

		if (!check_user_operation_interval_by_uid('modify', $this->user_id, get_setting('modify_content_interval')))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('操作过于频繁, 请稍后再试')));
		}

		if (!$answer_info = $this->model('answer')->get_answer_by_id($_POST['answer_id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('回答不存在')));
		}

		if (! $question_info = $this->model('question')->get_question_info_by_id($answer_info['question_id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('问题不存在')));
		}

		set_user_operation_last_time_by_uid('modify', $this->user_id);

		if ($question_info['best_answer'])
		{
			$this->model('answer')->unset_best_answer($_POST['answer_id'], $this->user_id);
		}
		else
		{
			$this->model('answer')->set_best_answer($_POST['answer_id'], $this->user_id);
		}

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

}