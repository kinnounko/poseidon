
import pandas as pd
from sklearn.ensemble import RandomForestClassifier, RandomForestRegressor

from sklearn.metrics import r2_score
from sklearn.model_selection import train_test_split


#### Functions

def filter_time(df, thres):
    filt_df = df.loc[df.year > thres].copy()
    return filt_df

def filter_cause(df):
    filt_df = df.loc[df.causes == "Earthquake"].copy()
    return filt_df

def aggregate_per_year(df, col_to_aggregate, aggregation_method="count"):
    agg_df = df.groupby("year").agg(aggregation_method)[col_to_aggregate].reset_index()
    plt.plot(agg_df["year"], agg_df[col_to_aggregate])
    return agg_df


def remove_missing_cols(df, missing_thres=0.6):
    missing_df = (pd.DataFrame(df.isna().sum() / len(df))
                  .reset_index()
                  .rename(columns={"index": "column", 0: "missing"}))
    missing_df.sort_values(by="missing", ascending=False)

    cols_to_sel = missing_df.loc[missing_df.missing < missing_thres, "column"].values.tolist()

    return df[cols_to_sel]


def prepare_dataset(df):
    ### Decide on target
    target = ["intensity_soloviev_wav"]

    cols_to_predict = ["maximum_height_wav",
                       "focal_depth_wav",
                       "primary_magnitude_wav",
                      ]

    cols_to_sel = cols_to_predict + target

    missing_vals_conds = ((df.intensity_soloviev_wav.isna()) &
                          (df.primary_magnitude_wav.isna()))

    no_na_df = df.loc[~missing_vals_conds].copy()
    no_na_df = no_na_df.loc[~no_na_df.intensity_soloviev_wav.isna()]

    X = no_na_df[cols_to_predict]
    y = no_na_df[target]

    X_train, X_test, y_train, y_test = train_test_split(X, y, test_size=0.3, random_state=42)

    #### Impute missing values
    for col in X_train.columns:
        X_train[col].fillna(X_train[col].mean(skipna=True), inplace=True)
    for col in X_test.columns:
        print(col)
        X_test[col].fillna(X_test[col].mean(skipna=True), inplace=True)

    return [X_train, X_test, y_train, y_test]


##### Build model

def build_model(X_train, X_test, y_train, y_test):
    rf = RandomForestRegressor()
    trained_model = rf.fit(X_train, y_train)

    #### Test on test
    y_pred = trained_model.predict(X_test)

    return y_pred, r2_score(y_test, y_pred)


if __name__ == "main":
### Read data

    sources = pd.read_csv("data/sources.csv")
    waves = pd.read_csv("data/waves.csv")


    waves.columns = waves.columns.str.lower()
    sources.columns = sources.columns.str.lower()

    #### First, we process all the information

    #Mapping different causes
    causes = {0:'Unknown',
              1:'Earthquake',
              2:'Questionable Earthquake',
              3:'Earthquake and Landslide',
              4:'Volcano and Earthquake',
              5:'Volcano, Earthquake, and Landslide',
              6:'Volcano',
              7:'Volcano and Landslide',
              8:'Landslide',
              9:'Meteorological',
              10:'Explosion',
              11:'Astronomical Tide'}

    sources['causes'] = sources['cause'].map(causes)

    filt_waves = filter_time(waves, thres=1900)
    filt_sources = filter_time(sources, thres=1900)

    earthquake_df = filter_cause(filt_sources)
    earthquake_df_sel = remove_missing_cols(earthquake_df, missing_thres=0.6)
    earthquake_df_sel_indonesia = earthquake_df_sel.loc[earthquake_df_sel.country == "INDONESIA"].copy()

    #### Merge both sources
    merged_with_sources = (earthquake_df_sel.merge(filt_sources["source_id", "causes"],
                                                   how="left", on=["source_id"], suffixes=("_wav",  "_sou")))

    merged_with_sources["hour_wav"] = merged_with_sources["hour_wav"].apply(lambda x: str(x)[:-2])
    merged_with_sources["minute_wav"] = merged_with_sources["minute_wav"].apply(lambda x: str(x)[:-2])

    merged_with_sources["date"] = (merged_with_sources["year_wav"].map(str) + "-"
                                   + merged_with_sources["month_wav"]
                                   + "-" + merged_with_sources["day_wav"])

    merged_with_sources["hour"] = (merged_with_sources["hour_wav"] + ":"
                                   + merged_with_sources["minute_wav"])


    merged_with_sources["date"] = pd.to_datetime(merged_with_sources["date"])
    merged_with_sources.sort_values(by="date", inplace=True)
    X_train, X_test, y_train, y_test = prepare dataset(merged_with_sources)
    y_pred, rsquared = build_model(X_train, X_test, y_train, y_test)





