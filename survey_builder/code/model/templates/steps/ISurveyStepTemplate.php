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

/**
 * Interface ISurveyStepTemplate
 */
interface ISurveyStepTemplate extends IEntity
{

    /**
     * @return ISurveyTemplate;
     */
    public function survey();

    /**
     * @return string
     */
    public function title();

    /**
     * @return string
     */
    public function friendlyName();

    /**
     * @return string
     */
    public function content();

    /**
     * @return int
     */
    public function order();

    /**
     * @return bool
     */
    public function canSkip();

    /**
     * @return ISurveyQuestionTemplate[]
     */
    public function getDependsOn();
}