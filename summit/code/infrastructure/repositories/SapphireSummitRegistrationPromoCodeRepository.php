<?php

/**
 * Copyright 2015 OpenStack Foundation
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * http://www.apache.org/licenses/LICENSE-2.0
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 **/
class SapphireSummitRegistrationPromoCodeRepository
    extends SapphireRepository
    implements ISummitRegistrationPromoCodeRepository
{

    public function __construct()
    {
        parent::__construct(new SummitRegistrationPromoCode);
    }

    /**
     * @param int $summit_id
     * @param int $page
     * @param int $page_size
     * @param string $term
     * @param string $sort_by
     * @param string $sort_dir
     * @return array
     */
    public function searchByTermAndSummitPaginated($summit_id, $type, $page= 1, $page_size = 10, $term = '', $sort_by = 'code', $sort_dir = 'asc')
    {
        $offset = ($page - 1 ) * $page_size;
        $sort = '';
        switch(strtolower($sort_by))
        {
            case 'code':
                $sort = ' ORDER BY `Code` '.strtoupper($sort_dir);
                break;
            case 'type':
                $sort = ' ORDER BY Type '.strtoupper($sort_dir);
                break;
        }

        $having = '1=1';

        if (!empty($term)) {
            $having .= " AND (`Code` LIKE '%{$term}%' OR CodeFirstName LIKE '%{$term}%' OR CodeLastName LIKE '%{$term}%'
                        OR CodeEmail LIKE '%{$term}%' OR SpeakerFirstName LIKE '%{$term}%' OR SpeakerLastName LIKE '%{$term}%'
                        OR SpeakerEmail LIKE '%{$term}%' OR OwnerFirstName LIKE '%{$term}%'
                        OR OwnerLastName LIKE '%{$term}%' OR OwnerEmail LIKE '%{$term}%' OR SponsorName LIKE '%{$term}%'";

            if (is_int($term)) {
                $having .= " OR SpeakerID = '{$term}' OR OwnerID = '{$term}')";
            } else {
                $having .= ")";
            }
        }

        if ($type) {
            $having .= " AND Type = '{$type}'";
        }

        $query = <<<SQL
SELECT PCode.ID AS CodeID, PCode.`Code`,PCode.ClassName,PCode.EmailSent,PCode.Redeemed,PCode.Source,
IF(SC.Type,SC.Type,MC.Type) AS Type, SC.SpeakerID, MC.OwnerID, SPC.SponsorID, MC.FirstName AS CodeFirstName,
MC.LastName AS CodeLastName, MC.Email AS CodeEmail, S.FirstName AS SpeakerFirstName, S.LastName AS SpeakerLastName,
M2.Email AS SpeakerEmail, M.FirstName AS OwnerFirstName, M.Surname AS OwnerLastName, M.Email AS OwnerEmail,
C.`Name` AS SponsorName, CONCAT(M3.FirstName,' ',M3.Surname) AS Creator
FROM SummitRegistrationPromoCode AS PCode
LEFT JOIN SpeakerSummitRegistrationPromoCode AS SC ON SC.ID = PCode.ID
LEFT JOIN MemberSummitRegistrationPromoCode AS MC ON MC.ID = PCode.ID
LEFT JOIN SponsorSummitRegistrationPromoCode AS SPC ON SPC.ID = PCode.ID
LEFT JOIN PresentationSpeaker AS S ON SC.SpeakerID = S.ID
LEFT JOIN Member AS M ON MC.OwnerID = M.ID
LEFT JOIN Company AS C ON SPC.SponsorID = C.ID
LEFT JOIN Member AS M2 ON S.ID = M2.ID
LEFT JOIN Member AS M3 ON PCode.CreatorID = M3.ID
WHERE SummitID = {$summit_id}
HAVING {$having}
{$sort}
SQL;

        $res       = DB::query($query);
        $count     = $res->numRecords();
        $query     .= ($page_size) ? " LIMIT {$offset}, {$page_size}" : "";
        $res       = DB::query($query);
        $data = array();

        foreach ($res as $code) {
            $source = ($code['Creator'] != '') ? $code['Creator'] : $code['Source'];
            $code_array = array(
                'id' => $code['CodeID'],
                'code' => $code['Code'],
                'email_sent' => intval($code['EmailSent']),
                'redeemed' => intval($code['Redeemed']),
                'source' => $source,
                'type' => $code['Type'],
                'sponsor'  => $code['SponsorName']
            );

            if ($code['SpeakerID']) {
                $code_array['owner'] = $code['SpeakerFirstName'].' '.$code['SpeakerLastName'];
                $code_array['owner_email'] = $code['SpeakerEmail'];
            } elseif ($code['OwnerID']) {
                $code_array['owner'] = $code['OwnerFirstName'].' '.$code['OwnerLastName'];
                $code_array['owner_email'] = $code['OwnerEmail'];
            } else {
                $code_array['owner'] = $code['CodeFirstName'].' '.$code['CodeLastName'];
                $code_array['owner_email'] = $code['CodeEmail'];
            }

            $data[] = $code_array;
        }

        return array($page, $page_size, $count, $data);
    }

    /**
     * @param int $summit_id
     * @param string $type
     * @param string $prefix
     * @param int $company_id
     * @param int $limit
     * @return ISummitRegistrationPromoCode[]
     */
    public function getFreeByTypeAndSummit($summit_id, $type, $prefix = '', $company_id = null, $limit)
    {
        $where = "SummitID = {$summit_id} AND Type = '{$type}'";
        $where .= (!empty($prefix)) ? " AND `Code` LIKE '{$prefix}%'" : "";

        switch ($type) {
            case 'ACCEPTED':
            case 'ALTERNATE':
                $where .= " AND IFNULL(SpeakerID, 0) = 0";
                $promocodes = SpeakerSummitRegistrationPromoCode::get()->where($where);
                break;
            case 'VIP':
            case 'ATC':
            case 'MEDIA ANALYST':
                $where .= " AND IFNULL(OwnerID, 0) = 0 AND IFNULL(FirstName, '') = ''";
                $where .= " AND IFNULL(LastName, '') = '' AND IFNULL(Email, '') = ''";
                $promocodes = MemberSummitRegistrationPromoCode::get()->where($where);
                break;
            case 'SPONSOR':
                $where .= " AND SponsorID = {$company_id} AND IFNULL(OwnerID, 0) = 0 AND IFNULL(FirstName, '') = ''";
                $where .= " AND IFNULL(LastName, '') = '' AND IFNULL(Email, '') = ''";
                $promocodes = SponsorSummitRegistrationPromoCode::get()->where($where);
                break;
        }

        return $promocodes->sort("Code")->limit($limit);
    }


    /**
     * @param int $summit_id
     * @param int $page
     * @param int $page_size
     * @param string $term
     * @param string $sort_by
     * @param string $sort_dir
     * @return array
     */
    public function searchSponsorByTermAndSummitPaginated($summit_id, $page= 1, $page_size = 10, $term = '', $sort_by = 'sponsor', $sort_dir = 'asc')
    {
        $offset = ($page - 1 ) * $page_size;
        $sort = '';
        switch(strtolower($sort_by))
        {
            case 'sponsor':
                $sort = ' ORDER BY C.`Name` '.strtoupper($sort_dir);
                break;
        }

        $query = <<<SQL
SELECT C.ID,C.Name,GROUP_CONCAT(PC.Code SEPARATOR ', ') AS Codes
FROM SponsorSummitRegistrationPromoCode AS SPC
LEFT JOIN SummitRegistrationPromoCode AS PC ON PC.ID = SPC.ID
LEFT JOIN Company AS C ON C.ID = SPC.SponsorID
WHERE SummitID = {$summit_id}
GROUP BY SPC.SponsorID
HAVING (C.Name LIKE '%{$term}%' OR Codes LIKE '%{$term}%' )
{$sort}
SQL;

        $res       = DB::query($query);
        $count     = $res->numRecords();
        $res       = DB::query($query." LIMIT {$offset}, {$page_size}");
        $data = array();

        foreach ($res as $code) {
            $code_array = array(
                'id' => $code['ID'],
                'sponsor' => $code['Name'],
                'codes' => $code['Codes'],
            );

            $data[] = $code_array;
        }

        return array($page, $page_size, $count, $data);
    }

    /**
     * @param int $summit_id
     * @param string $code
     * @return ISummitRegistrationPromoCode
     */
    public function getByCode($summit_id, $code)
    {
        $query = new QueryObject();
        $query->addAndCondition(QueryCriteria::equal('SummitID', $summit_id));
        $query->addAndCondition(QueryCriteria::equal('Code', $code));

        return $this->getBy($query);
    }

    /**
     * @param int $summit_id
     * @param int $company_id
     * @return ISummitRegistrationPromoCode[]
     */
    public function getBySponsor($summit_id, $company_id)
    {
        $promo_codes = SponsorSummitRegistrationPromoCode::get()->where("SummitID = $summit_id AND SponsorID = $company_id");

        return $promo_codes;
    }

    /**
     * @param int $summit_id
     * @return ArrayList
     */
    public function getGroupedBySponsor($summit_id)
    {
        $query = <<<SQL
        SELECT C.ID AS ID,C.Name,GROUP_CONCAT(PC.Code SEPARATOR ', ') AS Codes
        FROM SponsorSummitRegistrationPromoCode AS SPC
        LEFT JOIN SummitRegistrationPromoCode AS PC ON PC.ID = SPC.ID
        LEFT JOIN Company AS C ON C.ID = SPC.SponsorID
        WHERE PC.SummitID = {$summit_id} GROUP BY SPC.SponsorID ORDER BY C.Name LIMIT 20
SQL;
        $db_result = DB::query($query);
        $result = new ArrayList();
        foreach ($db_result as $sponsor) {
            $result->push(new ArrayData($sponsor));
        }

        return $result;
    }

    /**
     * @param int $summit_id
     * @param int $owner_id
     * @return ISummitRegistrationPromoCode
     */
    public function getByOwner($summit_id, $owner_id)
    {
        $promo_codes = MemberSummitRegistrationPromoCode::get()->where("SummitID = $summit_id AND MemberSummitRegistrationPromoCode.OwnerID = $owner_id");

        return $promo_codes;
    }

    /**
     * @param int $summit_id
     * @param int $speaker_id
     * @return ISummitRegistrationPromoCode
     */
    public function getBySpeaker($summit_id, $speaker_id)
    {
        $promo_codes = SpeakerSummitRegistrationPromoCode::get()->where("SummitID = $summit_id AND SpeakerID = $speaker_id");

        return $promo_codes;
    }

    /**
     * @param int $summit_id
     * @param string $email
     * @return ISummitRegistrationPromoCode
     */
    public function getByEmail($summit_id, $email)
    {
        $promo_codes = MemberSummitRegistrationPromoCode::get()->where("SummitID = $summit_id AND Email = '$email'");

        return $promo_codes;
    }

}