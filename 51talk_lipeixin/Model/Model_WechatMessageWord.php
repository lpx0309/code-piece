<?php
/**
 * Created by PhpStorm.
 * User: lipeixin
 * Date: 2017/7/26
 * Time: 15:53
 */
class Model_WechatMessageWord extends DataModel
{
    const STUDYPLAN_TYPE = 1;
    const OC_TYPE = 2;
    const USERHOLIDY_TYPE = 3;

    public function getWordByAlias($alias, $status = 1)
    {
        $where = 'alias="' . $alias . '"';
        if($status){
            $where .= ' and status ='. $status;
        }
        return Load::loadData('WechatMessageWord')->getWordByWhere($where, 'u_word,a_word');
    }

    public function getWordById($id)
    {
        return Load::loadData('WechatMessageWord')->getWordByWhere('id=' . $id);
    }

    public function getWordList()
    {
        return Load::loadData('WechatMessageWord')->getWordsByWhere('1=1');
    }

    public function addWord($data)
    {
        return Load::loadData('WechatMessageWord')->addWord($data);
    }

    public function updateWordById($id, $data)
    {
        return Load::loadData('WechatMessageWord')->updateWordByWhere('id=' . $id, $data);
    }

    public function delWordById($id)
    {
        return Load::loadData('WechatMessageWord')->delWordByWhere('id=' . $id);
    }

    /**
     * 增加请假通知
     *
     * @param $uid
     * @return bool
     *
     * @author zpt<zhaopengtao@51talk.com>
     */
    public function syncUserHolidyToWechat($uid, $id)
    {
        if (is_numeric($uid) && $uid > 0 && is_numeric($id) && $id > 0) {
            $textInfo = Load::loadModel('WechatMessageWord')->getWordByAlias('user_holiday');
            if(empty($textInfo)){
                return false;
            }
            $isUserCycle = (new \Logic\Comm\IsUserCycle())->getIsUserCycleByUid($uid);
            if (!empty($isUserCycle) && isset($isUserCycle['admin_id'])) {

                $userHolidayInfo = (new \Logic\Comm\StuPoint\UserHoliday())->getUserHolidayById($id);
                if (!empty($userHolidayInfo) && $userHolidayInfo['status'] == 0) {
                    return $this->addMessageToPool($uid, $isUserCycle['admin_id'], $userHolidayInfo, 2, $textInfo);
                } elseif (empty($userHolidayInfo)) {
                    return false;
                }
                if (!empty($userHolidayInfo) && $userHolidayInfo['status'] == 1) {
                    return $this->addMessageToPool($uid, $isUserCycle['admin_id'], $userHolidayInfo, 1, $textInfo);
                }
            }
        }
        return false;
    }

    /**
     * 添加
     *
     * @param $uid
     * @param $isUserCycle
     * @param $userHolidayInfo
     * @return bool
     *
     * @author zpt<zhaopengtao@51talk.com>
     */
    private function addMessageToPool($uid, $adminId, $userHolidayInfo, $type = 2, $textInfo)
    {
        //通知黑鸟
        $uInfo = (new \Logic\Comm\User())->getUserInfoByUid($uid);
        $uInfoMessage = '(' . $uInfo['nick_name'] . '-' . $uid . '-' . $uInfo['mobile'] . ')';
        $wechatMessage = array();
        $type == 1 ? $userHolidayType = '请假' : $userHolidayType = '取消请假';
        $wechatMessage['user_id'] = $uid;
        $wechatMessage['admin_id'] = $adminId;
        $wechatMessage['operator'] = 0;
        $wechatMessage['type'] = self::USERHOLIDY_TYPE;
        $userHolidayTextInfo = $userHolidayInfo['start'].'到'.$userHolidayInfo['end'];
        $wechatMessage['u_text_info'] = str_replace(array('{$userholiday_info}', '{$type}'), array($userHolidayTextInfo, $userHolidayType), $textInfo['u_word']);
        $wechatMessage['a_text_info'] = str_replace(array('{$u_info}', '{$userholiday_info}', '{$type}'), array($uInfoMessage, $userHolidayTextInfo, $userHolidayType), $textInfo['a_word']);
        $wechatMessage['sendto_who'] = 3;
        $wechatMessage['add_time'] = date('Y-m-d H:i:s');

        try {
            (new \Logic\Comm\WechatMessage())->addWechatMessage($wechatMessage);
        } catch (\Exception $e) {
            \Tool\Log::write("userholidyFail", 'user.holiday.send.fail', \Tool\Log::ERROR);
        }
        return true;
    }

    /**
     * 学习计划
     * @author libo <libo01@51talk.com>
     * @date   2017-07-31 15:26:15
     * @param $user_id
     * @param $image_url
     * @return bool
     */
    public function addStudyPlan($user_id, $image_url)
    {
        //通知黑鸟
        $isUserCycle = (new \Logic\Comm\IsUserCycle())->getIsUserCycleByUid($user_id);
        if (!empty($isUserCycle)) {
            $textInfo = Load::loadModel('WechatMessageWord')->getWordByAlias('u_studyplan');
            $wechatMessage = array();
            $wechatMessage['user_id'] = $user_id;
            $wechatMessage['admin_id'] = $isUserCycle['admin_id'];
            $wechatMessage['operator'] = Http::session('admin_user_id');
            $wechatMessage['type'] = self::STUDYPLAN_TYPE;
            $wechatMessage['u_img_info'] = $image_url;
            $wechatMessage['u_text_info'] = $textInfo['u_word'];
            $wechatMessage['a_text_info'] = str_replace(['{$uid}'],[$user_id], $textInfo['a_word']);
            $wechatMessage['sendto_who'] = 1;
            $wechatMessage['add_time'] = date('Y-m-d H:i:s');
            try{
                (new \Logic\Comm\WechatMessage())->addWechatMessage($wechatMessage);
            }catch(\Exception $e){
                \Tool\Log::write("studyPlanFail",'user.study_plan.send.fail', \Tool\Log::ERROR);
            }
        }

        return true;
    }
}