import pandas as pd
import numpy as np
from sklearn.ensemble import RandomForestClassifier, RandomForestRegressor

from sklearn.metrics import r2_score
from sklearn.model_selection import train_test_split


# Functions

def filter_time(df, thres):
    filt_df = df.loc[df.year > thres].copy()
    return filt_df


def filter_cause(df):
    filt_df = df.loc[df.cause_code == "Earthquake"].copy()
    return filt_df


def remove_missing_cols(df, missing_thres=0.6):
    missing_df = (pd.DataFrame(df.isna().sum() / len(df))
                  .reset_index()
                  .rename(columns={"index": "column", 0: "missing"}))
    missing_df.sort_values(by="missing", ascending=False)

    cols_to_sel = missing_df.loc[missing_df.missing <
                                 missing_thres, "column"].copy().values.tolist()

    return df[cols_to_sel].copy()


def prepare_dataset(df):
    # Decide on target
    #df = df.dropna()
    #df.fillna(df.mean(skipna=True), inplace=True)
    target = ["maximum_water_height"]

    cols_to_predict = ["latitude",
                       "longitude",
                       "primary_magnitude",
                       ]

    missing_vals_conds = ((df.primary_magnitude.isna()) &
                          (df.latitude.isna()) &
                          (df.longitude.isna()))

    no_na_df = df.loc[~missing_vals_conds].copy()
    no_na_df = no_na_df.loc[~no_na_df.maximum_water_height.isna()].copy()

    X = no_na_df[cols_to_predict].copy()
    y = no_na_df[target].copy()

    X_train, X_test, y_train, y_test = train_test_split(
        X, y, test_size=0.3, random_state=42)

    # Impute missing values
    for col in X_train.columns:
        X_train[col].fillna(X_train[col].mean(skipna=True), inplace=True)
    for col in X_test.columns:
        X_test[col].fillna(X_test[col].mean(skipna=True), inplace=True)

    return [X_train, X_test, y_train, y_test]


# Build model

def build_model(X_train, X_test, y_train, y_test):
    rf = RandomForestRegressor(max_depth=2,
                               max_features=2/3,
                               min_samples_leaf=5,
                               n_estimators=200,
                               oob_score=True,
                               random_state=3)
    trained_model = rf.fit(X_train, y_train.values.ravel())

    # Test on test
    y_pred = trained_model.predict(X_test)

    return [y_pred, r2_score(y_test, y_pred)]


if __name__ == "__main__":
    # Read data
    print("Read files")
    sources = pd.read_csv("data/sources.csv", sep='\t',
                          lineterminator='\r', encoding='latin1')
    waves = pd.read_csv("data/waves.csv", sep='\t',
                        lineterminator='\r', encoding='latin1')

    waves.columns = waves.columns.str.lower()
    sources.columns = sources.columns.str.lower()

    # First, we process all the information

    # Mapping different causes
    causes = {0: 'Unknown',
              1: 'Earthquake',
              2: 'Questionable Earthquake',
              3: 'Earthquake and Landslide',
              4: 'Volcano and Earthquake',
              5: 'Volcano, Earthquake, and Landslide',
              6: 'Volcano',
              7: 'Volcano and Landslide',
              8: 'Landslide',
              9: 'Meteorological',
              10: 'Explosion',
              11: 'Astronomical Tide'}

    sources['cause_code'] = sources['cause_code'].map(causes)

    filt_waves = filter_time(waves, thres=1900)
    filt_sources = filter_time(sources, thres=1900)

    earthquake_df = filter_cause(filt_sources)
    earthquake_df_sel = remove_missing_cols(earthquake_df, missing_thres=0.6)
    earthquake_df_sel_indonesia = earthquake_df_sel.loc[earthquake_df_sel.country == "INDONESIA"].copy(
    )

    # Merge both sources
    merged_with_sources = (earthquake_df_sel.merge(filt_sources[["id", "cause_code"]],
                                                   how="left", on=["id"], suffixes=("_wav",  "_sou")))

    merged_with_sources["month"] = merged_with_sources["month"].apply(
        lambda x: str(x)[:-2])
    merged_with_sources["day"] = merged_with_sources["day"].apply(lambda x: str(x)[
                                                                  :-2])

    merged_with_sources["hour"] = merged_with_sources["hour"].apply(
        lambda x: str(x)[:-2])
    merged_with_sources["minute"] = merged_with_sources["minute"].apply(
        lambda x: str(x)[:-2])

    merged_with_sources["date"] = (merged_with_sources["year"].map(str) + "-"
                                   + merged_with_sources["month"]
                                   + "-" + merged_with_sources["day"])

    merged_with_sources["hour"] = (merged_with_sources["hour"] + ":"
                                   + merged_with_sources["minute"])

    # merged_with_sources["date"] = pd.to_datetime(merged_with_sources["date"])
    # merged_with_sources.sort_values(by="date", inplace=True)

    print("Prepare dataset")
    X_train, X_test, y_train, y_test = prepare_dataset(merged_with_sources)
    print("Build model")
    y_pred, rsquared = build_model(X_train, X_test, y_train, y_test)
    print("Rsquared is: {}".format(np.round(rsquared, 3)))
    pd.DataFrame(y_pred, columns=["predictions"]).to_csv(
        "predictions.csv", index=False)
