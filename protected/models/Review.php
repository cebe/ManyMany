<?php

/**
 * Table attributes:
 * @property string $reviewer_id
 * @property string $song_id
 * @property string $review
 *
 * Relation attributes:
 * @property Reviewer $reviewer
 * @property Song $song
 */
class Review extends CActiveRecord {
	public static function model($className = __CLASS__) {
		return parent::model($className);
	}

	public function tableName() {
		return 'review';
	}

	public function rules() {
		return array();
	}

	public function relations() {
		return array(
			'reviewer' => array(self::BELONGS_TO, 'Reviewer', 'reviewer_id'),
			'song' => array(self::BELONGS_TO, 'Song', 'song_id'),
		);
	}

	public function attributeLabels() {
		return array(
			'reviewer_id' => 'Reviewer ID',
			'song_id' => 'Song ID',
			'review' => 'Review',
		);
	}

	public function search() {
		$criteria = new CDbCriteria;

		$criteria->compare('reviewer_id', $this->reviewer_id, true);
		$criteria->compare('song_id', $this->song_id, true);
		$criteria->compare('review', $this->review, true);

		return new CActiveDataProvider($this, array(
			'criteria' => $criteria,
		));
	}
}