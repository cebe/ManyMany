<?php

/**
 * Table attributes:
 * @property string $id
 * @property string $name
 * @property string $artist
 * @property string $album
 *
 * Relation attributes:
 * @property Genre[] $genres
 * @property SongGenre[] $hasGenres
 * @property Review[] $reviews
 * @property Reviewer[] $reviewers
 *
 * Virtual attributes:
 * @property array $genreNames
 */
class Song extends CActiveRecord {
	/**
	 * @var string Filter/search input
	 */
	public $genre;
	/**
	 * @var string Filter/search input
	 */
	public $review;
	/**
	 * @var string Filter/search input
	 */
	public $reviewer;
	/**
	 * @var CDbCriteria Criteria applied in search method
	 */
	public $criteria;

	private $_genreNames;

	public static function model($className = __CLASS__) {
		return parent::model($className);
	}

	public function tableName() {
		return 'song';
	}

	public function rules() {
		return array(
			array('name, artist, album', 'safe', 'on' => 'search, SongGenre, Review'),
			array('genre, review, reviewer', 'safe', 'on' => 'SongGenre, Review'),
		);
	}

	public function relations() {
		return array(
			'hasGenres' => array(self::HAS_MANY, 'SongGenre', 'song_id'),
			'genres' => array(self::HAS_MANY, 'Genre', 'genre_id',
				'through' => 'hasGenres'),
			'reviews' => array(self::HAS_MANY, 'Review', 'song_id'),
			'reviewers' => array(self::HAS_MANY, 'Reviewer', 'reviewer_id',
				'through' => 'reviews'),
		);
	}

	public function getGenreNames() {
		if ($this->_genreNames === null) {
			$this->_genreNames = array('pri' => array(), 'sec' => array());
			/** @noinspection PhpUndefinedFieldInspection */
			$genres = $this->with('hasGenres', 'hasGenres.genre')->hasGenres;
			if ($genres) {
				foreach ($genres as $genre) {
					$this->_genreNames[$genre->is_primary ? 'pri' : 'sec'][] =
						$genre->genre->name;
				}
			}
		}
		return $this->_genreNames;
	}

	public function attributeLabels() {
		return array(
			'id' => 'Song ID',
			'name' => 'Title',
			'artist' => 'Artist',
			'album' => 'Album',
		);
	}

	public function search() {
		$criteria = new CDbCriteria;
		$sort = new CSort;

		$sort->attributes = array(
			'song.name' => array(
				'asc' => 'song.name asc',
				'desc' => 'song.name desc',
			),
			'song.artist' => array(
				'asc' => 'song.artist asc',
				'desc' => 'song.artist desc',
			),
			'song.album' => array(
				'asc' => 'song.album asc',
				'desc' => 'song.album desc',
			),
			'review' => array(
				'asc' => 't.review asc',
				'desc' => 't.review desc',
			),
			'reviewer.name' => array(
				'asc' => 'reviewer.name asc',
				'desc' => 'reviewer.name desc',
			),
			'genres' => array(
				'asc' => 'gsq.gnames asc',
				'desc' => 'gsq.gnames desc',
			),
		);

		if ($this->scenario === 'SongGenre') {
			$dpModel = new SongGenre;

			$criteria->compare('song.name', $this->name, true);
			$criteria->compare('song.artist', $this->artist, true);
			$criteria->compare('song.album', $this->album, true);
			$criteria->compare('genre.name', $this->genre, true);

			if (array_key_exists('genres', $sort->directions)) {
				$criteria->join =
					'left join (
					    select group_concat(distinct g.name separator " ") as gnames,
					      sg.song_id as sid
					    from song_genre sg
					    inner join genre g on g.id = sg.genre_id
					    group by sg.song_id
					) gsq on gsq.sid = t.song_id';
			}
		} elseif ($this->scenario === 'Review') {
			$dpModel = new Review;

			$criteria->compare('song.name', $this->name, true);
			$criteria->compare('song.artist', $this->artist, true);
			$criteria->compare('song.album', $this->album, true);
			$criteria->compare('t.review', $this->review, true);
			$criteria->compare('reviewer.name', $this->reviewer, true);
			if (array_key_exists('genres', $sort->directions)
				|| $this->genre !== null
			) {
				$criteria->join =
					'left join (
					    select group_concat(distinct g.name separator " ") as gnames,
					      sg.song_id as sid
					    from song_genre sg
					    inner join genre g on g.id = sg.genre_id
					    group by sg.song_id
					) gsq on gsq.sid = t.song_id';
			}
			if ($this->genre !== null) {
				$genres = preg_split('/[\s,.]+/', $this->genre);
				$genre = array_shift($genres);
				$criteria->compare('gsq.gnames', $genre, true);
				if ($genres) {
					foreach ($genres as $genre) {
						$criteria->compare('gsq.gnames', $this->genre, true, 'or');
					}
				}
			}

		} else {
			$dpModel = new Song;

			$criteria->compare('name', $this->name, true);
			$criteria->compare('artist', $this->artist, true);
			$criteria->compare('album', $this->album, true);
		}

		if ($this->criteria) {
			$criteria->mergeWith($this->criteria);
		}

		return new CActiveDataProvider($dpModel, array(
			'criteria' => $criteria,
			'sort' => $sort,
		));
	}

}