<?php

/**
 * League admin controller
 *
 * Class LeagueAdminController
 */
class LeagueAdminController extends PageController {

	/**
	 * League settings validation rules
	 *
	 * @var array
	 */
	public $league_settings_rules = [
		'name'        => ['required', 'max:255'],
		'description' => ['required'],
		'url'         => ['url'],
		'private'     => ['boolean'],

		'money'       => ['required', 'integer'],
		'units'       => ['required', 'max:16'],
		'extra_weeks' => ['required', 'integer', 'between:1,12'],
	];

	/**
	 * League settings page
	 *
	 * @param League $league
	 */
	public function settings(League $league) {
		$league->load('admins');

		$this->layout->content = View::make('league.admin.settings', compact('league'));
		$this->layout->content->validation_rules = $this->league_settings_rules;
	}

	/**
	 * Updating league settings page
	 *
	 * @param League $league
	 *
	 * @return $this|\Illuminate\Http\RedirectResponse
	 */
	public function storeSettings(League $league) {
		$validator = Validator::make(Input::all(), $this->league_settings_rules);

		if ($validator->fails()) {
			Notification::error('Duder, check your input');

			return Redirect::back()->withInput()->withErrors($validator);
		}

		// Overwrites
		$league->fill(Input::only([
			'name', 'description', 'url', 'private', 'money', 'units', 'extra_weeks'
		]));

		if ($league->save()) {
			// TODO: End date checking

			Notification::success('Changes saved!');

			return Redirect::route('league.admin.settings', ['league' => $league->slug]);
		} else {
			Notification::error('Database error, try again later!');

			return Redirect::back()->withInput();
		}
	}

	/**
	 * List league's movies
	 *
	 * @param League $league
	 */
	public function movies(League $league) {
		$league->load(['movies' => function ($query) {
			/** @var \Illuminate\Database\Eloquent\Builder|Movie $query */
			$query->ordered();
		}, 'movies.movie']);

		$this->layout->content = View::make('league.admin.movies', compact('league'));
	}

	/**
	 * List movies that can be added to the league
	 *
	 * @param League $league
	 */
	public function addableMovies(League $league) {

		list($date_range, $movies) = $this->getAddableMovies($league);

		$this->layout->content = View::make('league.admin.addmovies', compact('league', 'movies', 'date_range'));
	}


	/**
	 * Add movies to a league
	 *
	 * @param League $league
	 *
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function addMovies(League $league) {
		$movie_ids = Input::get('movie');
		if (count($movie_ids) == 0) {
			Notification::error('Please choose movies to add');

			return Redirect::back();
		}

		list($date_range, $movies) = $this->getAddableMovies($league, $movie_ids);

		/** @type Movie $movie */
		foreach ($movies as $movie) {
			$league->movies()->create(['movie_id' => $movie->id, 'latest_earnings_id' => $movie->latest_earnings_id]);
		}

		$league->updateLeagueDates();

		Notification::success(count($movies) . ' movie(s) have been added!');

		return Redirect::route('league.admin.movies', ['league' => $league->slug]);
	}


	/**
	 * Removes a LeagueMovie from the league
	 *
	 * @param League $league
	 *
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function removeMovie(League $league) {

		$movie_id = intval(Input::get('movie'));

		/** @var LeagueMovie $movie */
		if (! $movie_id || ! ($movie = $league->movies()->find($movie_id))) {
			Notification::error('Movie not found');

			return Redirect::back();
		}

		$movie->delete();
		$league->updateLeagueDates();

		Notification::success('Movie removed from the league');

		return Redirect::back();
	}

	/**
	 * Get moves that can be added by the league, optionally filtered by ID's
	 *
	 * @param League $league
	 * @param array  $ids
	 *
	 * @return array
	 */
	protected function getAddableMovies(League $league, $movie_ids = []) {
		$date_range = [Carbon::now(), $league->maxLastMovieDate()];

		$query = Movie::query();

		if (count($movie_ids)) {
			$query->whereIn('id', $movie_ids);
		}
		// Remove movies already added
		if ($league->movies->count()) {
			$query->whereNotIn('id', $league->movies->fetch('movie_id')->toArray());
		} else {
			// If no movies set start date to a more saner value
			$date_range[1] = Carbon::now()->addWeeks(Config::get('draft.maximum_weeks'));
		}
		$query->whereBetween('release', $date_range);
		$query->orderBy('release', 'asc');

		$movies = $query->get();

		return [$date_range, $movies];
	}

	public $team_valiation_rules = [
		'username'  => ['required', 'exists:users,username'],
		'team_name' => ['required', 'between:1,64'],
	];

	/**
	 * Show user's teams
	 *
	 * @param League $league
	 */
	public function teams(League $league) {
		$validation_rules = $this->team_valiation_rules;

		$league->load('teams', 'teams.users');

		$this->layout->content = View::make('league.admin.teams', compact('league', 'validation_rules'));
	}


	/**
	 * Add a team to the league
	 *
	 * @param League $league
	 *
	 * @return $this|\Illuminate\Http\RedirectResponse
	 */
	public function addTeam(League $league) {
		$validation = Validator::make(Input::all(), $this->team_valiation_rules);
		if ($validation->fails()) {
			Notification::error('Please check your input and try again');

			return Redirect::back()->withInput()->withErrors($validation);
		}

		$user = User::whereUsername(Input::get('username'))->first();

		// Check if user is already in a team
		$check = DB::table('league_teams')
		           ->where('league_teams.league_id', $league->id)
		           ->join('league_team_user', 'league_teams.id', '=', 'league_team_user.league_team_id')
		           ->where('league_team_user.user_id', $user->id)->count();

		if ($check) {
			Notification::error('This user is already in a team in this league');

			return Redirect::back()->withInput();
		}

		// All good
		DB::beginTransaction();

		/** @type LeagueTeam $team */
		$team = $league->teams()->create(['name' => Input::get('team_name')]);
		$team->users()->attach($user->id);

		DB::commit();

		Notification::success('Team has been added to your league!');

		return Redirect::back();
	}

	/**
	 * Removing a team from the league.
	 *
	 * @param League $league
	 *
	 * @return \Illuminate\Http\RedirectResponse
	 * @throws Exception
	 */
	public function removeTeam(League $league) {
		/** @type LeagueTeam $team */
		$team = $league->teams()->where('id', Input::get('team'))->first();

		if(!$team) {
			Notification::error('Team not found');

			return Redirect::back();
		}

		$team->delete();

		Notification::success('Team has been removed');

		return Redirect::back();
	}

	/**
	 * Display admins for the league
	 *
	 * @param League $league
	 */
	public function admins(League $league) {
		$this->layout->content = View::make('league.admin.admins', compact('league'));
	}

	/**
	 * Add admins to the league
	 *
	 * @param League $league
	 *
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function addAdmin(League $league) {
		$user = User::whereUsername(Input::get('username'))->first();

		if (! $user) {
			Notification::error('User not found');

			return Redirect::back()->withInput();
		}
		if (! $league->admins()->where('users.id', $user->id)->count()) {
			$league->admins()->attach($user);

			Notification::success('Admin added');
		} else {
			Notification::warning('User is already an admin');
		}

		return Redirect::back();
	}

	/**
	 * Remove an admin from the league
	 *
	 * @param League $league
	 *
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function removeAdmin(League $league) {
		$user = $league->admins()->where('users.id', Input::get('user'))->first();

		if (! $user) {
			Notification::warning('User isn\'t an admin');
		} elseif ($user->id == Auth::user()->id) {
			Notification::error('You can\'t remove yourself');
		} else {
			$league->admins()->detach($user->id);
			Notification::success('User removed from admins');
		}

		return Redirect::back();
	}


} 