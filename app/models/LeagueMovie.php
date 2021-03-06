<?php

/**
 * LeagueMovie
 *
 * @property-read \League $league
 * @property-read \Movie $movie
 * @property-read \MovieEarning $latestEarnings
 * @property integer $id
 * @property integer $league_id
 * @property integer $movie_id
 * @property integer $price
 * @property integer $latest_earnings_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @method static \Illuminate\Database\Query\Builder|\LeagueMovie whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\LeagueMovie whereLeagueId($value)
 * @method static \Illuminate\Database\Query\Builder|\LeagueMovie whereMovieId($value)
 * @method static \Illuminate\Database\Query\Builder|\LeagueMovie wherePrice($value)
 * @method static \Illuminate\Database\Query\Builder|\LeagueMovie whereLatestEarningsId($value)
 * @method static \Illuminate\Database\Query\Builder|\LeagueMovie whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\LeagueMovie whereUpdatedAt($value)
 * @method static \LeagueMovie ordered()
 * @property-read \Illuminate\Database\Eloquent\Collection|\LeagueTeam[] $teams
 */
class LeagueMovie extends Eloquent {

	/**
	 * The attributes that are mass assignable.
	 *
	 * @type array
	 */
	protected $fillable = ['league_id', 'movie_id', 'latest_earnings_id', 'price'];

	/**
	 * The league this entry is in
	 *
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function league() {
		return $this->belongsTo('League');
	}

	/**
	 * The movie this entry represents
	 *
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function movie() {
		return $this->belongsTo('Movie');
	}

	/**
	 * Teams this movie is owned by
	 *
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
	 */
	public function teams() {
		return $this->belongsToMany('LeagueTeam', 'league_team_movies')->withTimestamps();
	}

	/**
	 * The latest earning usable in the league context
	 *
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function latestEarnings() {
		return $this->belongsTo('MovieEarning');
	}

	/**
	 * All movie's earnings
	 *
	 * @return \Illuminate\Database\Eloquent\Relations\HasMany
	 */
	public function earnings() {
		return $this->hasMany('MovieEarning','movie_id','league_movie_id');
	}


	/**
	 * Order movies by the release date
	 *
	 * @param \Illuminate\Database\Eloquent\Builder $query
	 */
	public function scopeOrdered(Illuminate\Database\Eloquent\Builder $query) {
		$query->join('movies', 'league_movies.movie_id', '=', 'movies.id');
		$query->orderBy('movies.release', 'ASC');
		$query->select('league_movies.*');
	}

}